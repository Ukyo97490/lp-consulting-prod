/* LP CONSULTING : administration (application JavaScript connectée à l'API /admin/api/…).
 *
 *  - les contenus et projets sont lus et enregistrés via l'API (session + jeton CSRF) ;
 *  - chaque enregistrement est publié immédiatement et conservé dans l'historique (restauration possible) ;
 *  - les images sont redimensionnées dans le navigateur, puis vérifiées et réencodées par le serveur.
 */
(function () {
  "use strict";

  var BASE = document.querySelector('meta[name="base-path"]').content;
  var CSRF = document.querySelector('meta[name="csrf-token"]').content;
  var MISSIONS = ["etudes-techniques", "preparation-chantier", "direction-travaux"];
  var STATUS = { won: "Offre remportée", lost: "Offre non retenue", works: "Chantier réalisé" };

  var state = null;       // { content, projects, library, unread, user }
  var view = document.getElementById("view");
  var dirty = false;
  var busy = false;

  /* =================================================================
     OUTILS
     ================================================================= */
  function esc(v) {
    return String(v == null ? "" : v).replace(/[&<>"']/g, function (c) {
      return { "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" }[c];
    });
  }
  function clone(o) { return JSON.parse(JSON.stringify(o)); }
  function set(obj, path, val) {
    var ks = path.split("."), o = obj;
    ks.slice(0, -1).forEach(function (k) { if (o[k] == null || typeof o[k] !== "object") o[k] = {}; o = o[k]; });
    o[ks[ks.length - 1]] = val;
  }
  function site(path) { return BASE + "/" + (path || ""); }
  function fmtDate(s) {
    var d = new Date(String(s).replace(" ", "T"));
    return isNaN(d) ? esc(s) : d.toLocaleDateString("fr-FR") + " " + d.toLocaleTimeString("fr-FR", { hour: "2-digit", minute: "2-digit" });
  }
  function toast(msg, type) {
    var t = document.getElementById("toast");
    t.textContent = msg;
    t.className = "toast show " + (type || "ok");
    clearTimeout(toast.t);
    toast.t = setTimeout(function () { t.className = "toast"; }, 4500);
  }

  /** Appel de l'API. data : objet (envoyé en JSON) ou FormData ; absent = lecture (GET). */
  function api(path, data) {
    var opt = { method: data ? "POST" : "GET", credentials: "same-origin", headers: { Accept: "application/json", "X-CSRF-Token": CSRF } };
    if (data instanceof FormData) opt.body = data;
    else if (data) { opt.body = JSON.stringify(data); opt.headers["Content-Type"] = "application/json"; }
    return fetch(site("admin/api/" + path), opt).then(function (r) {
      return r.json().catch(function () { return { error: "Réponse inattendue du serveur (" + r.status + ")." }; }).then(function (j) {
        if (r.status === 401 && j.login) { dirty = false; location.href = j.login; throw new Error(j.error); }
        if (!r.ok || j.error) throw new Error(j.error || "Erreur " + r.status);
        return j;
      });
    });
  }

  /* =================================================================
     COMPOSANTS DE FORMULAIRE
     ================================================================= */
  var uid = 0;
  function fText(label, path, value, o) {
    o = o || {};
    var id = "f" + (++uid);
    var max = o.max ? ' data-max="' + o.max + '"' : "";
    var input = o.rows
      ? '<textarea id="' + id + '" data-path="' + path + '" rows="' + o.rows + '"' + max + ">" + esc(value) + "</textarea>"
      : '<input id="' + id + '" type="' + (o.type || "text") + '" data-path="' + path + '" value="' + esc(value) + '"' + max + ">";
    return '<div class="fld' + (o.wide ? " wide" : "") + '"><label for="' + id + '">' + esc(label) + "</label>" + input +
      (o.help ? '<small class="help">' + esc(o.help) + "</small>" : "") + "</div>";
  }
  function fLines(label, path, arr) {
    var id = "f" + (++uid);
    return '<div class="fld"><label for="' + id + '">' + esc(label) + '</label><textarea id="' + id + '" data-path="' + path + '" data-lines rows="' + Math.max(3, arr.length + 1) + '">' +
      esc(arr.join("\n")) + '</textarea><small class="help">Un élément par ligne.</small></div>';
  }
  function fSelect(label, path, value, options, help) {
    var id = "f" + (++uid);
    return '<div class="fld"><label for="' + id + '">' + esc(label) + '</label><select id="' + id + '" data-path="' + path + '">' +
      Object.keys(options).map(function (k) { return '<option value="' + esc(k) + '"' + (k === value ? " selected" : "") + ">" + esc(options[k]) + "</option>"; }).join("") +
      "</select>" + (help ? '<small class="help">' + esc(help) + "</small>" : "") + "</div>";
  }
  function fCheck(label, path, value) {
    return '<label class="check"><input type="checkbox" data-path="' + path + '"' + (value ? " checked" : "") + "> " + esc(label) + "</label>";
  }
  function imgOptions(value) {
    var opts = state.library.slice();
    if (value && opts.indexOf(value) < 0) opts.push(value);
    return opts.map(function (p) {
      return '<option value="' + esc(p) + '"' + (p === value ? " selected" : "") + ">" + esc(p.split("/").pop()) + (p.indexOf("uploads/") === 0 ? " (envoyée)" : "") + "</option>";
    }).join("");
  }
  function fImage(label, path, value) {
    var id = "f" + (++uid);
    return '<div class="fld wide img-field"><label for="' + id + '">' + esc(label) + '</label><div class="img-row"><img class="img-preview" src="' + esc(site(value)) + '" alt="">' +
      '<div class="img-controls"><select id="' + id + '" data-path="' + path + '" data-image>' + imgOptions(value) + "</select>" +
      '<label class="upload-btn"><input type="file" accept="image/jpeg,image/png,image/webp" data-upload> Envoyer une nouvelle image</label>' +
      '<small class="help">JPG, PNG ou WebP. L’image est redimensionnée à 1 600 px maximum.</small></div></div></div>';
  }
  function repRow(cols, row) {
    return '<div class="rep-row"><div class="rep-fields">' + Object.keys(cols).map(function (k) {
      var c = cols[k], v = row ? row[k] : "";
      return '<label class="rep-fld' + (c[1] === "textarea" ? " grow" : "") + '"><span>' + esc(c[0]) + "</span>" +
        (c[1] === "textarea" ? '<textarea data-key="' + k + '" rows="3">' + esc(v) + "</textarea>" : '<input type="text" data-key="' + k + '" value="' + esc(v) + '">') + "</label>";
    }).join("") + '</div><div class="rep-tools"><button type="button" class="ico" data-move="-1" title="Monter">↑</button><button type="button" class="ico" data-move="1" title="Descendre">↓</button><button type="button" class="ico danger" data-remove title="Supprimer">×</button></div></div>';
  }
  function fRepeater(label, path, rows, cols, addLabel) {
    return '<div class="fld wide"><span class="lbl">' + esc(label) + '</span><div class="repeater" data-path="' + path + '" data-cols="' + esc(JSON.stringify(cols)) + '">' +
      rows.map(function (r) { return repRow(cols, r); }).join("") + '</div><button type="button" class="btn-add" data-add>+ ' + esc(addLabel || "Ajouter une ligne") + "</button></div>";
  }
  function card(legend, inner) { return '<fieldset class="card"><legend>' + esc(legend) + "</legend>" + inner + "</fieldset>"; }
  function formWrap(id, inner, previewPath, saveLabel, label) {
    return '<form class="edit-form" id="' + id + '" data-label="' + esc(label || "") + '" novalidate>' + inner +
      '<div class="save-bar"><span class="dirty-note">Modifications non enregistrées</span>' +
      (previewPath != null ? '<a class="btn-sec" href="' + esc(site(previewPath)) + '" target="_blank" rel="noopener">Voir la page</a>' : "") +
      '<button class="btn-main" type="submit">' + (saveLabel || "Enregistrer et publier") + "</button></div></form>";
  }

  /** Lit un formulaire et applique les valeurs dans l'objet cible. */
  function readForm(form, target) {
    form.querySelectorAll("[data-path]").forEach(function (f) {
      if (f.classList.contains("repeater")) return;
      var path = f.getAttribute("data-path"), v;
      if (f.type === "checkbox") v = f.checked;
      else if (f.hasAttribute("data-lines")) v = f.value.split("\n").map(function (s) { return s.trim(); }).filter(Boolean);
      else v = f.value.replace(/\r\n/g, "\n").trim();
      set(target, path, v);
    });
    form.querySelectorAll(".repeater").forEach(function (rep) {
      var rows = [];
      rep.querySelectorAll(".rep-row").forEach(function (row) {
        var o = {}, filled = false;
        row.querySelectorAll("[data-key]").forEach(function (f) {
          o[f.getAttribute("data-key")] = f.value.trim();
          if (f.value.trim()) filled = true;
        });
        if (filled) rows.push(o);
      });
      set(target, rep.getAttribute("data-path"), rows);
    });
  }

  /* =================================================================
     VUES
     ================================================================= */
  var MENU = [
    ["tableau", "Tableau de bord"], ["accueil", "Page d’accueil"], ["missions", "Pages missions"], ["projets", "Projets / références"],
    ["references", "Page Références"], ["infos", "Coordonnées et mentions"], ["messages", "Demandes de contact"], ["historique", "Historique"], ["compte", "Mon compte"]
  ];
  function renderMenu(active) {
    document.getElementById("side-nav").innerHTML = MENU.map(function (m) {
      var badge = m[0] === "messages" && state.unread ? ' <em class="badge-count">' + state.unread + "</em>" : "";
      return '<a href="#' + m[0] + '"' + (m[0] === active ? ' class="active"' : "") + "><span>" + esc(m[1]) + badge + "</span></a>";
    }).join("");
  }

  function vTableau() {
    var p = state.projects;
    var vis = p.filter(function (x) { return x.visible; }).length;
    var feat = p.filter(function (x) { return x.visible && x.featured; }).length;
    return "<h1>Tableau de bord</h1><p class=\"intro\">Modifiez les textes et les projets du site. Chaque enregistrement est <b>publié immédiatement</b> et conservé dans l’<a href=\"#historique\">historique</a> : une version précédente peut être restaurée à tout moment.</p>" +
      '<div class="kpis"><a class="kpi" href="#projets"><strong>' + p.length + "</strong><span>projets</span><small>" + vis + " affichés · " + feat + " sur l’accueil</small></a>" +
      '<a class="kpi" href="#missions"><strong>3</strong><span>pages missions</span><small>Études · Préparation · Direction</small></a>' +
      '<a class="kpi" href="#messages"><strong>' + state.unread + "</strong><span>demande" + (state.unread > 1 ? "s" : "") + " non lue" + (state.unread > 1 ? "s" : "") + "</span><small>formulaire de contact</small></a>" +
      '<a class="kpi" href="' + esc(site()) + '" target="_blank" rel="noopener"><strong>↗</strong><span>voir le site</span><small>version en ligne</small></a></div>' +
      '<h2>Actions rapides</h2><div class="quick"><a href="#projet/nouveau"><b>+ Ajouter un projet</b><span>Nouvelle référence avec photo, rôle, montant et statut.</span></a>' +
      '<a href="#accueil"><b>Modifier l’accueil</b><span>Accroche, chiffres clés, présentation, expertise.</span></a>' +
      '<a href="#messages"><b>Lire les demandes</b><span>Messages reçus par le formulaire de contact.</span></a></div>';
  }

  function vAccueil() {
    var h = state.content.home, b = "home.";
    return "<h1>Page d’accueil</h1>" + formWrap("form-content",
      card("Référencement", fText("Titre de la page (Google)", b + "seo_title", h.seo_title, { max: 60, wide: true }) + fText("Description (Google)", b + "seo_description", h.seo_description, { rows: 2, max: 160, wide: true })) +
      card("Ouverture", fText("Sur-titre", b + "hero_eyebrow", h.hero_eyebrow) + fLines("Mots sous l’accroche", b + "hero_tags", h.hero_tags) +
        fText("Accroche", b + "hero_title", h.hero_title, { wide: true }) + fText("Fin de l’accroche (en bleu ciel)", b + "hero_highlight", h.hero_highlight) +
        fText("Texte d’introduction", b + "hero_lead", h.hero_lead, { rows: 3, wide: true }) + fImage("Photo de fond", b + "hero_image", h.hero_image)) +
      card("Chiffres clés", fRepeater("Chiffres", b + "stats", h.stats, { prefix: ["Préfixe"], value: ["Valeur"], label: ["Libellé"] }, "Ajouter un chiffre")) +
      card("Présentation du fondateur", fText("Sur-titre", b + "about_eyebrow", h.about_eyebrow) + fText("Titre", b + "about_title", h.about_title, { wide: true }) +
        fText("Chapeau", b + "about_lead", h.about_lead, { rows: 3, wide: true }) +
        fText("Texte", b + "about_text", h.about_text, { rows: 12, wide: true, help: "Laissez une ligne vide entre deux paragraphes." }) +
        fRepeater("Frise du parcours", b + "timeline", h.timeline, { title: ["Étape"], text: ["Détail", "textarea"] }, "Ajouter une étape") +
        fImage("Image", b + "about_image", h.about_image) + fText("Légende de l’image", b + "about_caption", h.about_caption, { wide: true }) +
        fLines("Groupes (encadré « Expérience au sein de »)", b + "groups", h.groups)) +
      card("Bloc missions", fText("Sur-titre", b + "missions_eyebrow", h.missions_eyebrow) + fText("Titre", b + "missions_title", h.missions_title) +
        fText("Texte", b + "missions_text", h.missions_text, { rows: 3, wide: true }) + '<p class="help wide">Le contenu des trois cartes se modifie dans <a href="#missions">Pages missions</a>.</p>') +
      card("Encadré d’expertise (COMPOSTYRÈNE)", fText("Sur-titre", b + "expertise_eyebrow", h.expertise_eyebrow) + fText("Titre", b + "expertise_title", h.expertise_title) +
        fText("Texte", b + "expertise_text", h.expertise_text, { rows: 6, wide: true, help: "Laissez une ligne vide entre deux paragraphes." }) +
        fText("Libellé du lien", b + "expertise_link_label", h.expertise_link_label) + fText("Lien (PDF ou page)", b + "expertise_link", h.expertise_link, { help: "Chemin sur le site (docs/…) ou adresse https://" }) +
        fRepeater("Chiffres de l’encadré", b + "expertise_figures", h.expertise_figures, { value: ["Valeur"], label: ["Libellé"] }, "Ajouter un chiffre")) +
      card("Références, appel à l’action et contact", fText("Références : sur-titre", b + "refs_eyebrow", h.refs_eyebrow) + fText("Références : titre", b + "refs_title", h.refs_title) +
        fText("Références : texte", b + "refs_text", h.refs_text, { rows: 2, wide: true }) +
        '<p class="help wide">Les 4 projets affichés sont ceux marqués « Mis en avant » dans <a href="#projets">Projets</a>.</p>' +
        fText("Bandeau : titre", b + "cta_title", h.cta_title, { wide: true }) + fText("Bandeau : texte", b + "cta_text", h.cta_text, { rows: 2, wide: true }) +
        fText("Contact : titre", b + "contact_title", h.contact_title) + fText("Contact : texte", b + "contact_text", h.contact_text, { rows: 2, wide: true })),
      "", null, "Page d’accueil");
  }

  function vMissions(slug) {
    if (MISSIONS.indexOf(slug) < 0) slug = MISSIONS[0];
    var m = state.content.missions[slug], b = "missions." + slug + ".";
    return "<h1>Pages missions</h1><div class=\"subtabs\">" + MISSIONS.map(function (s) {
      var x = state.content.missions[s];
      return '<a href="#missions/' + s + '"' + (s === slug ? ' class="active"' : "") + ">" + esc(x.num + ". " + x.short) + "</a>";
    }).join("") + "</div>" + formWrap("form-content",
      card("Carte sur l’accueil", fText("Nom court", b + "short", m.short) + fText("Numéro", b + "num", m.num) +
        fText("Accroche de la carte", b + "card_text", m.card_text, { rows: 2, wide: true }) + fLines("Points clés de la carte", b + "card_points", m.card_points)) +
      card("Référencement", fText("Titre (Google et titre de section)", b + "seo_title", m.seo_title, { max: 60, wide: true }) +
        fText("Description (Google)", b + "seo_description", m.seo_description, { rows: 2, max: 160, wide: true })) +
      card("En-tête de page", fText("Sur-titre", b + "eyebrow", m.eyebrow, { wide: true }) + fText("Titre principal (H1)", b + "h1", m.h1, { rows: 2, wide: true }) +
        fImage("Image de fond", b + "hero_image", m.hero_image)) +
      card("Introduction", fText("Texte", b + "intro", m.intro, { rows: 6, wide: true, help: "Laissez une ligne vide entre deux paragraphes." }) +
        fImage("Image", b + "split_image", m.split_image) +
        fText("Étiquette de l’image", b + "split_label", m.split_label, { help: "« Visuel de projet », « Photo de chantier » ou vide." })) +
      card("Prestations", fRepeater("Prestations", b + "services", m.services, { title: ["Titre"], text: ["Description", "textarea"] }, "Ajouter une prestation")) +
      card("Objectif et appel à l’action", fText("Notre objectif", b + "objective", m.objective, { rows: 3, wide: true }) +
        fText("Bandeau : titre", b + "cta_title", m.cta_title, { wide: true }) + fText("Bandeau : texte", b + "cta_text", m.cta_text, { rows: 2, wide: true })),
      slug, null, "Mission : " + m.short);
  }

  function vProjets() {
    var rows = state.projects.map(function (p, i) {
      return "<tr" + (p.visible ? "" : ' class="off"') + '><td><img class="thumb" src="' + esc(site(p.image)) + '" alt=""></td>' +
        "<td><b>" + esc(p.title) + "</b><small>" + esc(p.client) + "</small></td><td>" + (p.category === "etudes" ? "Études" : "Travaux") + "</td>" +
        '<td><span class="pill pill-' + esc(p.status) + '">' + esc(STATUS[p.status]) + "</span></td><td>" + esc(p.budget) + "</td>" +
        '<td><button class="ico-btn" data-act="visible" data-i="' + i + '" title="Afficher / masquer"><span class="switch' + (p.visible ? " on" : "") + '"></span></button></td>' +
        '<td><button class="ico-btn" data-act="featured" data-i="' + i + '" title="Mettre en avant sur l’accueil"><span class="star' + (p.featured ? " on" : "") + '">★</span></button></td>' +
        '<td class="order">' + (i > 0 ? '<button class="ico-btn" data-act="up" data-i="' + i + '" title="Monter">↑</button>' : "") +
        (i < state.projects.length - 1 ? '<button class="ico-btn" data-act="down" data-i="' + i + '" title="Descendre">↓</button>' : "") + "</td>" +
        '<td class="actions"><a class="btn-sec sm" href="#projet/' + encodeURIComponent(p.id) + '">Modifier</a><button class="btn-sec sm danger" data-act="delete" data-i="' + i + '">Supprimer</button></td></tr>';
    }).join("");
    return '<div class="head-row"><h1>Projets / références</h1><a class="btn-main" href="#projet/nouveau">+ Ajouter un projet</a></div>' +
      '<p class="intro">L’ordre de la liste est celui de l’affichage sur le site. Les projets marqués d’une étoile apparaissent sur l’accueil (4 maximum). Chaque action est publiée immédiatement.</p>' +
      '<div class="table-wrap"><table class="list"><thead><tr><th></th><th>Projet</th><th>Rubrique</th><th>Statut</th><th>Montant</th><th>Affiché</th><th>Accueil</th><th>Ordre</th><th></th></tr></thead><tbody>' + rows + "</tbody></table></div>";
  }

  function vProjet(id) {
    var p = state.projects.filter(function (x) { return x.id === id; })[0];
    var isNew = !p;
    p = p || { id: "", category: "travaux", title: "", client: "", role: "", duration: "", budget: "", budget_note: "", status: "works", image: "img/nrl-m24-possession.jpg", image_type: "photo", description: "", featured: false, visible: true };
    return '<p class="back"><a href="#projets">← Retour à la liste</a></p><h1>' + (isNew ? "Ajouter un projet" : "Modifier le projet") + "</h1>" +
      formWrap("form-project",
        '<input type="hidden" data-pid value="' + esc(p.id) + '">' +
        card("Projet", fText("Titre de l’opération *", "title", p.title, { wide: true }) +
          fSelect("Rubrique", "category", p.category, { etudes: "Études de projets et conception-réalisation", travaux: "Direction et conduite de travaux" }) +
          fSelect("Statut affiché", "status", p.status, STATUS) +
          fText("Client / maître d’ouvrage", "client", p.client) + fText("Rôle exercé", "role", p.role) +
          fText("Durée", "duration", p.duration, { help: "Ex. : 10 mois de travaux" }) + fText("Montant", "budget", p.budget, { help: "Ex. : 6 M€" }) +
          fText("Précision sur le montant", "budget_note", p.budget_note, { help: "Facultatif. Ex. : dont lot battage 11 M€" }) +
          fText("Description", "description", p.description, { rows: 4, wide: true })) +
        card("Image", fImage("Image du projet", "image", p.image) +
          fSelect("Type d’image", "image_type", p.image_type, { photo: "Photo de chantier", visual: "Visuel de projet (perspective, rendu 3D)", none: "Sans étiquette" }, "Les perspectives doivent être signalées comme « Visuel de projet ».")) +
        card("Affichage", fCheck("Afficher sur le site", "visible", p.visible) + fCheck("Mettre en avant sur l’accueil", "featured", p.featured)),
        "references", isNew ? "Ajouter le projet" : "Enregistrer et publier");
  }

  function vReferences() {
    var r = state.content.references, b = "references.";
    return "<h1>Page Références</h1><p class=\"intro\">Textes de la page. Les fiches projets se gèrent dans <a href=\"#projets\">Projets / références</a>.</p>" + formWrap("form-content",
      card("Référencement", fText("Titre de la page (Google)", b + "seo_title", r.seo_title, { max: 70, wide: true }) + fText("Description (Google)", b + "seo_description", r.seo_description, { rows: 2, max: 160, wide: true })) +
      card("Contenu", fText("Titre", b + "title", r.title, { wide: true }) + fText("Texte d’introduction", b + "text", r.text, { rows: 3, wide: true }) +
        fImage("Image de fond", b + "hero_image", r.hero_image) +
        fText("Titre de la rubrique Études", b + "group_etudes", r.group_etudes, { wide: true }) + fText("Titre de la rubrique Travaux", b + "group_travaux", r.group_travaux, { wide: true }) +
        fText("Note de bas de page", b + "note", r.note, { rows: 3, wide: true }) +
        fText("Bandeau : titre", b + "cta_title", r.cta_title, { wide: true }) + fText("Bandeau : texte", b + "cta_text", r.cta_text, { rows: 2, wide: true })),
      "references", null, "Page Références");
  }

  function vInfos() {
    var s = state.content.site, l = state.content.legal;
    return "<h1>Coordonnées et mentions légales</h1>" + formWrap("form-content",
      card("Coordonnées (pied de page, contact)", fText("Téléphone", "site.phone", s.phone) + fText("E-mail (reçoit les demandes du formulaire)", "site.email", s.email, { type: "email" }) +
        fText("Zone d’intervention", "site.zone", s.zone) + fText("Adresse du site", "site.domain", s.domain, { help: "Affichée dans le pied de page." }) +
        fText("Texte du pied de page", "site.footer_text", s.footer_text, { rows: 2, wide: true })) +
      card("Mentions légales", fText("Raison sociale", "legal.company", l.company) + fText("Forme juridique et capital", "legal.legal_form", l.legal_form) +
        fText("Adresse du siège", "legal.address", l.address, { wide: true }) + fText("SIRET", "legal.siret", l.siret) + fText("Directeur de la publication", "legal.director", l.director) +
        fText("Hébergeur", "legal.host", l.host, { rows: 2, wide: true }) + fText("Conception du site", "legal.designer", l.designer) +
        '<p class="help wide">Les valeurs encore entre crochets [ ] sont surlignées en jaune sur la page Mentions légales.</p>'),
      "mentions-legales", null, "Coordonnées et mentions");
  }

  function vMessages() {
    api("messages").then(function (r) {
      state.unread = r.unread;
      renderMenu("messages");
      var box = document.getElementById("msg-list");
      if (!box) return;
      if (!r.messages.length) { box.innerHTML = '<p class="intro">Aucune demande reçue pour le moment.</p>'; return; }
      box.innerHTML = r.messages.map(function (m) {
        return '<article class="msg' + (m.read_at ? "" : " unread") + '" data-id="' + m.id + '"><header><div><b>' + esc(m.name) + "</b>" + (m.company ? " · " + esc(m.company) : "") +
          "<small>" + fmtDate(m.created_at) + " · " + esc(m.mission) + (m.mailed == 1 ? "" : ' · <span class="warn">e-mail non transmis</span>') + "</small></div>" +
          '<div class="msg-tools">' +
          '<button class="btn-sec sm" data-msg="read" data-read="' + (m.read_at ? 0 : 1) + '">' + (m.read_at ? "Marquer non lu" : "Marquer lu") + "</button>" +
          '<button class="btn-sec sm danger" data-msg="delete">Supprimer</button></div></header>' +
          '<p class="msg-contact"><a href="mailto:' + esc(m.email) + '">' + esc(m.email) + "</a>" + (m.phone ? ' · <a href="tel:' + esc(m.phone.replace(/[^0-9+]/g, "")) + '">' + esc(m.phone) + "</a>" : "") + "</p>" +
          '<div class="msg-body">' + esc(m.message).replace(/\n/g, "<br>") + "</div></article>";
      }).join("");
    }).catch(function (e) { toast(e.message, "error"); });
    var mail = esc(state.content.site.email || "l’adresse indiquée dans Coordonnées et mentions");
    return '<h1>Demandes de contact</h1>' +
      '<aside class="msg-info"><h2>Où arrivent les demandes ?</h2>' +
      '<p>Chaque demande envoyée par le formulaire du site arrive <b>à deux endroits</b> : <b>ici</b>, où elle est toujours enregistrée, et <b>dans votre messagerie</b> (' + mail + '), d’où vous pouvez répondre directement au client. Une fois marquée comme lue, une demande s’affiche en grisé.</p>' +
      '<p><b>Les deux copies sont indépendantes</b> : supprimer une demande ici ne supprime pas l’e-mail reçu, et inversement. La mention « e-mail non transmis » signale une demande qui n’existe qu’ici.</p>' +
      '<p class="help">Supprimez les demandes traitées depuis plus de trois ans (RGPD).</p></aside>' +
      '<div id="msg-list"><p class="intro">Chargement…</p></div>';
  }

  function vHistorique() {
    api("revisions").then(function (r) {
      var box = document.getElementById("rev-list");
      if (!box) return;
      box.innerHTML = '<div class="table-wrap"><table class="list"><thead><tr><th>Date</th><th>Modification</th><th>Par</th><th></th></tr></thead><tbody>' +
        r.revisions.map(function (v, i) {
          return "<tr><td>" + fmtDate(v.created_at) + "</td><td>" + esc(v.label) + (i === 0 ? ' <span class="pill pill-won">version en ligne</span>' : "") + "</td><td>" + esc(v.name || v.email || "Installation") + "</td>" +
            '<td class="actions">' + (i === 0 ? "" : '<button class="btn-sec sm" data-restore="' + v.id + '">Restaurer</button>') + "</td></tr>";
        }).join("") + "</tbody></table></div>";
    }).catch(function (e) { toast(e.message, "error"); });
    return '<h1>Historique des versions</h1><p class="intro">Une version complète du site (textes et projets) est enregistrée à chaque modification. Restaurer une version la republie immédiatement ; la version actuelle reste dans l’historique. Les 100 dernières versions sont conservées.</p><div id="rev-list"><p class="intro">Chargement…</p></div>';
  }

  function vCompte() {
    var u = state.user;
    return "<h1>Mon compte</h1><p class=\"intro\">Connecté en tant que <b>" + esc(u.name || u.email) + "</b> (" + esc(u.email) + ").</p>" +
      '<form class="edit-form" id="form-password" novalidate>' + card("Changer de mot de passe",
        '<div class="fld"><label for="pw-cur">Mot de passe actuel</label><input id="pw-cur" type="password" name="current" autocomplete="current-password" required></div>' +
        '<div class="fld"><label for="pw-new">Nouveau mot de passe</label><input id="pw-new" type="password" name="password" autocomplete="new-password" minlength="12" required><small class="help">12 caractères minimum, avec des lettres et des chiffres.</small></div>' +
        '<div class="fld"><label for="pw-conf">Confirmation</label><input id="pw-conf" type="password" name="confirm" autocomplete="new-password" required></div>') +
      '<div class="save-bar"><button class="btn-main" type="submit">Changer le mot de passe</button></div></form>';
  }

  /* =================================================================
     ROUTAGE (ancre de l'adresse : #accueil, #projet/<id>…)
     ================================================================= */
  function route() {
    if (dirty && !confirm("Des modifications ne sont pas enregistrées. Quitter cette page ?")) {
      history.replaceState(null, "", route.last || "#tableau");
      return;
    }
    dirty = false;
    var h = (location.hash || "#tableau").slice(1).split("/");
    var tab = h[0], arg = decodeURIComponent(h[1] || "");
    var html;
    switch (tab) {
      case "accueil": html = vAccueil(); break;
      case "missions": html = vMissions(arg); break;
      case "projets": html = vProjets(); break;
      case "projet": html = vProjet(arg); tab = "projets"; break;
      case "references": html = vReferences(); break;
      case "infos": html = vInfos(); break;
      case "messages": html = vMessages(); break;
      case "historique": html = vHistorique(); break;
      case "compte": html = vCompte(); break;
      default: tab = "tableau"; html = vTableau();
    }
    route.last = location.hash;
    renderMenu(tab);
    view.innerHTML = html;
    enhance();
    document.body.classList.remove("menu-open");
    window.scrollTo(0, 0);
  }

  function enhance() {
    view.querySelectorAll("[data-max]").forEach(function (f) {
      var max = +f.getAttribute("data-max");
      var c = document.createElement("span");
      c.className = "counter";
      f.insertAdjacentElement("afterend", c);
      var upd = function () { c.textContent = f.value.length + " / " + max + " caractères conseillés"; c.classList.toggle("over", f.value.length > max); };
      f.addEventListener("input", upd); upd();
    });
    var form = view.querySelector("form.edit-form");
    if (form) {
      if (form.id !== "form-password") {
        form.addEventListener("input", markDirty);
        form.addEventListener("change", markDirty);
      }
      form.addEventListener("submit", onSubmit);
    }
  }
  function markDirty() {
    dirty = true;
    var form = view.querySelector("form.edit-form");
    if (form) form.classList.add("is-dirty");
  }

  function lock(form, on) {
    busy = on;
    var b = form && form.querySelector('button[type="submit"]');
    if (!b) return;
    b.disabled = on;
    if (on) { b.dataset.label = b.textContent; b.textContent = "Enregistrement…"; } else if (b.dataset.label) b.textContent = b.dataset.label;
  }

  function saveProjects(list, label) {
    return api("projects", { projects: list, label: label }).then(function (r) { state.projects = r.projects; return r; });
  }

  function onSubmit(e) {
    e.preventDefault();
    if (busy) return;
    var form = e.target, req;

    if (form.id === "form-password") {
      var f = form.elements;
      if (f.password.value !== f.confirm.value) { toast("La confirmation ne correspond pas au nouveau mot de passe.", "error"); return; }
      lock(form, true);
      api("account/password", { current: f.current.value, password: f.password.value }).then(function (r) {
        if (r.csrf) CSRF = r.csrf;
        form.reset(); toast("Mot de passe modifié.");
      }).catch(function (err) { toast(err.message, "error"); }).then(function () { lock(form, false); });
      return;
    }

    if (form.id === "form-content") {
      var content = clone(state.content);
      readForm(form, content);
      req = api("content", { content: content, label: form.getAttribute("data-label") || "Textes modifiés" }).then(function (r) { state.content = r.content; });
    } else if (form.id === "form-project") {
      var p = {};
      readForm(form, p);
      if (!p.title) { toast("Le titre du projet est obligatoire.", "error"); return; }
      var id = form.querySelector("[data-pid]").value, list = clone(state.projects);
      if (!id) { p.id = ""; list.unshift(p); }
      else list = list.map(function (x) { return x.id === id ? Object.assign({}, x, p, { id: id }) : x; });
      req = saveProjects(list, (id ? "Projet modifié : " : "Projet ajouté : ") + p.title);
    }
    lock(form, true);
    req.then(function () {
      dirty = false;
      form.classList.remove("is-dirty");
      toast(form.id === "form-project" ? "Projet enregistré et publié." : "Modifications enregistrées et publiées.");
      if (form.id === "form-project") location.hash = "#projets";
    }).catch(function (err) { toast(err.message, "error"); }).then(function () { lock(form, false); });
  }

  /* =================================================================
     IMAGES (redimensionnées dans le navigateur avant l'envoi)
     ================================================================= */
  function resizeImage(file) {
    return new Promise(function (resolve, reject) {
      if (!/^image\/(jpeg|png|webp)$/.test(file.type)) return reject(new Error("Format non accepté : JPG, PNG ou WebP."));
      if (file.size > 25 * 1024 * 1024) return reject(new Error("Image trop lourde (25 Mo maximum)."));
      var url = URL.createObjectURL(file), im = new Image();
      im.onload = function () {
        var r = Math.min(1, 1600 / Math.max(im.width, im.height));
        var c = document.createElement("canvas");
        c.width = Math.round(im.width * r); c.height = Math.round(im.height * r);
        var ctx = c.getContext("2d");
        ctx.fillStyle = "#fff"; ctx.fillRect(0, 0, c.width, c.height);
        ctx.drawImage(im, 0, 0, c.width, c.height);
        URL.revokeObjectURL(url);
        c.toBlob(function (b) { b ? resolve(b) : reject(new Error("Image illisible.")); }, "image/jpeg", 0.88);
      };
      im.onerror = function () { URL.revokeObjectURL(url); reject(new Error("Image illisible.")); };
      im.src = url;
    });
  }

  /* =================================================================
     ÉVÉNEMENTS GLOBAUX
     ================================================================= */
  document.addEventListener("click", function (e) {
    var t;
    if ((t = e.target.closest("[data-menu]"))) { document.body.classList.toggle("menu-open"); return; }
    if (document.body.classList.contains("menu-open") && !e.target.closest(".side")) document.body.classList.remove("menu-open");

    // Listes répétables
    if ((t = e.target.closest("[data-add]"))) {
      var rep = t.parentElement.querySelector(".repeater");
      rep.insertAdjacentHTML("beforeend", repRow(JSON.parse(rep.getAttribute("data-cols")), null));
      var f = rep.lastElementChild.querySelector("input, textarea"); if (f) f.focus();
      markDirty(); return;
    }
    if ((t = e.target.closest("[data-remove]"))) { t.closest(".rep-row").remove(); markDirty(); return; }
    if ((t = e.target.closest("[data-move]"))) {
      var row = t.closest(".rep-row"), dir = +t.getAttribute("data-move");
      if (dir < 0 && row.previousElementSibling) row.parentNode.insertBefore(row, row.previousElementSibling);
      if (dir > 0 && row.nextElementSibling) row.parentNode.insertBefore(row.nextElementSibling, row);
      markDirty(); return;
    }

    // Liste des projets
    if ((t = e.target.closest("[data-act]"))) {
      if (busy) return;
      var i = +t.getAttribute("data-i"), act = t.getAttribute("data-act"), P = clone(state.projects), label;
      if (act === "visible") { P[i].visible = !P[i].visible; label = (P[i].visible ? "Projet affiché : " : "Projet masqué : ") + P[i].title; }
      if (act === "featured") { P[i].featured = !P[i].featured; label = (P[i].featured ? "Mis en avant : " : "Retiré de l’accueil : ") + P[i].title; }
      if (act === "up" && i > 0) { P.splice(i - 1, 0, P.splice(i, 1)[0]); label = "Ordre des projets modifié"; }
      if (act === "down" && i < P.length - 1) { P.splice(i + 1, 0, P.splice(i, 1)[0]); label = "Ordre des projets modifié"; }
      if (act === "delete") {
        if (!confirm("Supprimer le projet « " + P[i].title + " » ? (Il restera récupérable dans l’historique.)")) return;
        label = "Projet supprimé : " + P[i].title;
        P.splice(i, 1);
      }
      busy = true;
      saveProjects(P, label).then(function () { route(); }).catch(function (err) { toast(err.message, "error"); }).then(function () { busy = false; });
      return;
    }

    // Demandes de contact
    if ((t = e.target.closest("[data-msg]"))) {
      var art = t.closest(".msg"), mid = art.getAttribute("data-id"), kind = t.getAttribute("data-msg");
      if (kind === "delete" && !confirm("Supprimer définitivement cette demande de l’administration ? L’e-mail reçu dans votre messagerie n’est pas supprimé.")) return;
      var req = kind === "delete" ? api("messages/" + mid + "/delete", {}) : api("messages/" + mid + "/read", { read: t.getAttribute("data-read") === "1" });
      req.then(function (r) {
        state.unread = r.unread;
        if (kind === "delete") { art.remove(); renderMenu("messages"); } else route();
      }).catch(function (err) { toast(err.message, "error"); });
      return;
    }

    // Historique
    if ((t = e.target.closest("[data-restore]"))) {
      if (!confirm("Restaurer cette version ? Elle sera publiée immédiatement.")) return;
      api("revisions/" + t.getAttribute("data-restore") + "/restore", {}).then(function (r) {
        state.content = r.content; state.projects = r.projects;
        toast("Version restaurée et publiée."); route();
      }).catch(function (err) { toast(err.message, "error"); });
    }
  });

  document.addEventListener("change", function (e) {
    var t = e.target;
    if (t.hasAttribute("data-image")) {
      t.closest(".img-field").querySelector(".img-preview").src = site(t.value);
      return;
    }
    if (t.hasAttribute("data-upload") && t.files[0]) {
      var field = t.closest(".img-field"), file = t.files[0], lab = t.closest(".upload-btn");
      lab.lastChild.textContent = " Envoi en cours…";
      resizeImage(file).then(function (blob) {
        var fd = new FormData();
        fd.append("image", blob, file.name.replace(/\.[^.]+$/, "") + ".jpg");
        return api("upload", fd);
      }).then(function (r) {
        state.library = r.library;
        var sel = field.querySelector("[data-image]");
        sel.innerHTML = imgOptions(r.path);
        sel.value = r.path;
        field.querySelector(".img-preview").src = site(r.path);
        lab.classList.add("has-file");
        lab.lastChild.textContent = " " + file.name;
        markDirty();
        toast("Image envoyée. Enregistrez pour la publier.");
      }).catch(function (err) {
        lab.lastChild.textContent = " Envoyer une nouvelle image";
        toast(err.message, "error");
      }).then(function () { t.value = ""; });
    }
  });

  window.addEventListener("hashchange", route);
  window.addEventListener("beforeunload", function (e) { if (dirty) { e.preventDefault(); e.returnValue = ""; } });

  /* =================================================================
     DÉMARRAGE
     ================================================================= */
  api("state").then(function (r) {
    state = r;
    route();
  }).catch(function (err) {
    view.innerHTML = '<h1>Chargement impossible</h1><p class="intro">' + esc(err.message) + "</p>";
  });
})();
