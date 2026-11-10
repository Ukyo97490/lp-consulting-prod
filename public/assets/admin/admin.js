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
  var STATUS = { won: "Offre remportée", lost: "Offre non retenue", works: "Chantier réalisé", prep: "Opération en cours" };

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
  /** Après régénération de la session : le formulaire de déconnexion doit aussi recevoir le nouveau jeton. */
  function setCsrf(token) {
    if (!token) return;
    CSRF = token;
    document.querySelectorAll('input[name="csrf"]').forEach(function (i) { i.value = token; });
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
    ["references", "Page Références"], ["infos", "Coordonnées et mentions"], ["textes", "Textes communs"], ["messages", "Demandes de contact"], ["historique", "Historique"], ["compte", "Mon compte"]
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
      '<a href="#messages"><b>Lire les demandes</b><span>Messages reçus par le formulaire de contact.</span></a></div>' +
      '<h2>Maintenance</h2><div class="quick"><button type="button" data-tool="minify"><b>Regénérer les CSS et JS allégés</b><span>Après une modification de style.css ou main.js.</span></button>' +
      '<button type="button" data-tool="images"><b>Optimiser les images</b><span>Crée les copies WebP manquantes et enregistre les dimensions.</span></button></div>';
  }

  function vAccueil() {
    var h = state.content.home, b = "home.";
    return "<h1>Page d’accueil</h1>" + formWrap("form-content",
      card("Référencement", fText("Titre de la page (Google)", b + "seo_title", h.seo_title, { max: 60, wide: true }) + fText("Description (Google)", b + "seo_description", h.seo_description, { rows: 2, max: 160, wide: true })) +
      card("Ouverture", fText("Sur-titre", b + "hero_eyebrow", h.hero_eyebrow) + fLines("Mots sous l’accroche", b + "hero_tags", h.hero_tags) +
        fText("Accroche", b + "hero_title", h.hero_title, { wide: true }) + fText("Fin de l’accroche (en bleu ciel)", b + "hero_highlight", h.hero_highlight) +
        fText("Texte d’introduction", b + "hero_lead", h.hero_lead, { rows: 3, wide: true }) +
        fText("Bouton 1 (vers les missions)", b + "hero_btn_missions", h.hero_btn_missions) + fText("Bouton 2 (vers le contact)", b + "hero_btn_contact", h.hero_btn_contact) +
        fImage("Photo de fond", b + "hero_image", h.hero_image)) +
      card("Chiffres clés", fRepeater("Chiffres", b + "stats", h.stats, { prefix: ["Préfixe"], value: ["Valeur"], label: ["Libellé"] }, "Ajouter un chiffre")) +
      card("Présentation du fondateur", fText("Sur-titre", b + "about_eyebrow", h.about_eyebrow) + fText("Titre", b + "about_title", h.about_title, { wide: true }) +
        fText("Chapeau", b + "about_lead", h.about_lead, { rows: 3, wide: true }) +
        fText("Texte", b + "about_text", h.about_text, { rows: 12, wide: true, help: "Laissez une ligne vide entre deux paragraphes." }) +
        fRepeater("Frise du parcours", b + "timeline", h.timeline, { title: ["Étape"], text: ["Détail", "textarea"] }, "Ajouter une étape") +
        fImage("Image", b + "about_image", h.about_image) + fText("Légende de l’image", b + "about_caption", h.about_caption, { wide: true }) +
        fText("Titre de l’encadré des groupes", b + "groups_title", h.groups_title) + fLines("Groupes (un par ligne)", b + "groups", h.groups)) +
      card("Bloc missions", fText("Sur-titre", b + "missions_eyebrow", h.missions_eyebrow) + fText("Titre", b + "missions_title", h.missions_title) +
        fText("Texte", b + "missions_text", h.missions_text, { rows: 3, wide: true }) + '<p class="help wide">Le contenu des trois cartes se modifie dans <a href="#missions">Pages missions</a>.</p>') +
      card("Encadré d’expertise (COMPOSTYRÈNE)", fText("Sur-titre", b + "expertise_eyebrow", h.expertise_eyebrow) + fText("Titre", b + "expertise_title", h.expertise_title) +
        fText("Texte", b + "expertise_text", h.expertise_text, { rows: 6, wide: true, help: "Laissez une ligne vide entre deux paragraphes." }) +
        fText("Libellé du lien", b + "expertise_link_label", h.expertise_link_label) + fText("Lien (PDF ou page)", b + "expertise_link", h.expertise_link, { help: "Chemin sur le site (docs/…) ou adresse https://" }) +
        fRepeater("Chiffres de l’encadré", b + "expertise_figures", h.expertise_figures, { value: ["Valeur"], label: ["Libellé"] }, "Ajouter un chiffre")) +
      card("Références, appel à l’action et contact", fText("Références : sur-titre", b + "refs_eyebrow", h.refs_eyebrow) + fText("Références : titre", b + "refs_title", h.refs_title) +
        fText("Références : texte", b + "refs_text", h.refs_text, { rows: 2, wide: true }) + fText("Références : bouton", b + "refs_button", h.refs_button) +
        '<p class="help wide">Les 4 projets affichés sont ceux marqués « Mis en avant » dans <a href="#projets">Projets</a>.</p>' +
        fText("Bandeau : titre", b + "cta_title", h.cta_title, { wide: true }) + fText("Bandeau : texte", b + "cta_text", h.cta_text, { rows: 2, wide: true }) +
        fText("Contact : sur-titre", b + "contact_eyebrow", h.contact_eyebrow) + fText("Contact : titre", b + "contact_title", h.contact_title) +
        fText("Contact : texte", b + "contact_text", h.contact_text, { rows: 2, wide: true }) +
        fText("Contact : texte sous le nom de l’entreprise", b + "contact_intro", h.contact_intro, { rows: 2, wide: true, help: "Les libellés du formulaire se modifient dans Textes communs." })),
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
      card("Référencement", fText("Titre de la page (Google)", b + "seo_title", m.seo_title, { max: 60, wide: true }) +
        fText("Description (Google)", b + "seo_description", m.seo_description, { rows: 2, max: 160, wide: true })) +
      card("En-tête de page", fText("Sur-titre", b + "eyebrow", m.eyebrow, { wide: true }) + fText("Titre principal (H1)", b + "h1", m.h1, { rows: 2, wide: true }) +
        fText("Titre de la section d’introduction", b + "intro_title", m.intro_title, { wide: true }) +
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
    p = p || { id: "", category: "travaux", title: "", client: "", role: "", duration: "", budget: "", budget_note: "", status: "works", image: "img/nrl-m24-possession.jpg", image_alt: "", image_type: "photo", description: "", featured: false, visible: true };
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
          fText("Texte alternatif de l’image", "image_alt", p.image_alt, { max: 125, wide: true, help: "Décrit l’image pour Google et les lecteurs d’écran. Vide : le titre du projet est utilisé." }) +
          fSelect("Type d’image", "image_type", p.image_type, { photo: "Photo de chantier", visual: "Visuel de projet (perspective, rendu 3D)", none: "Sans étiquette" }, "Les perspectives doivent être signalées comme « Visuel de projet ».")) +
        card("Affichage", fCheck("Afficher sur le site", "visible", p.visible) + fCheck("Mettre en avant sur l’accueil", "featured", p.featured)),
        "references", isNew ? "Ajouter le projet" : "Enregistrer et publier");
  }

  function vReferences() {
    var r = state.content.references, b = "references.";
    return "<h1>Page Références</h1><p class=\"intro\">Textes de la page. Les fiches projets se gèrent dans <a href=\"#projets\">Projets / références</a>.</p>" + formWrap("form-content",
      card("Référencement", fText("Titre de la page (Google)", b + "seo_title", r.seo_title, { max: 60, wide: true }) + fText("Description (Google)", b + "seo_description", r.seo_description, { rows: 2, max: 160, wide: true })) +
      card("Contenu", fText("Sur-titre et fil d’Ariane", b + "eyebrow", r.eyebrow) + fText("Titre", b + "title", r.title, { wide: true }) + fText("Texte d’introduction", b + "text", r.text, { rows: 3, wide: true }) +
        fImage("Image de fond", b + "hero_image", r.hero_image) +
        fRepeater("Chiffres clés", b + "stats", r.stats, { prefix: ["Préfixe"], value: ["Valeur"], label: ["Libellé"] }, "Ajouter un chiffre") +
        fText("Titre de la rubrique Études", b + "group_etudes", r.group_etudes, { wide: true }) + fText("Titre de la rubrique Travaux", b + "group_travaux", r.group_travaux, { wide: true }) +
        fText("Note de bas de page", b + "note", r.note, { rows: 3, wide: true }) +
        fText("Bandeau : titre", b + "cta_title", r.cta_title, { wide: true }) + fText("Bandeau : texte", b + "cta_text", r.cta_text, { rows: 2, wide: true })) +
      card("Étiquettes des fiches projets", fText("Statut « Offre remportée »", b + "status_won", r.status_won) + fText("Statut « Offre non retenue »", b + "status_lost", r.status_lost) +
        fText("Statut « Chantier réalisé »", b + "status_works", r.status_works) + fText("Statut « Opération en cours »", b + "status_prep", r.status_prep) +
        fText("Étiquette des visuels de projet", b + "badge_visual", r.badge_visual) + fText("Étiquette des photos de chantier", b + "badge_photo", r.badge_photo) +
        '<p class="help wide">Textes affichés sur les fiches de la page Références. Le choix du statut de chaque projet se fait dans <a href="#projets">Projets / références</a>.</p>'),
      "references", null, "Page Références");
  }

  function vInfos() {
    var s = state.content.site, l = state.content.legal;
    return "<h1>Coordonnées et mentions légales</h1>" + formWrap("form-content",
      card("Référencement de la page Mentions légales", fText("Titre de la page (Google)", "legal.seo_title", l.seo_title, { max: 60, wide: true }) + fText("Description (Google)", "legal.seo_description", l.seo_description, { rows: 2, max: 160, wide: true })) +
      card("Identité", fText("Nom de l’entreprise", "site.name", s.name, { help: "Titre du bloc contact, pied de page, onglet du navigateur." }) +
        fText("Signature", "site.tagline", s.tagline, { help: "Texte associé au logo (lecteurs d’écran, Google)." })) +
      card("Coordonnées (pied de page, contact)", fText("Téléphone", "site.phone", s.phone) + fText("E-mail (reçoit les demandes du formulaire)", "site.email", s.email, { type: "email" }) +
        fText("Zone d’intervention", "site.zone", s.zone) + fText("Adresse du site", "site.domain", s.domain, { help: "Affichée dans le pied de page." }) +
        fText("Texte du pied de page", "site.footer_text", s.footer_text, { rows: 2, wide: true })) +
      card("Mentions légales", fText("Raison sociale", "legal.company", l.company) + fText("Forme juridique et capital", "legal.legal_form", l.legal_form) +
        fText("Adresse du siège", "legal.address", l.address, { wide: true }) + fText("SIRET", "legal.siret", l.siret) + fText("Directeur de la publication", "legal.director", l.director) +
        fText("Hébergeur", "legal.host", l.host, { rows: 2, wide: true }) + fText("Conception du site", "legal.designer", l.designer) +
        '<p class="help wide">Les valeurs encore entre crochets [ ] sont surlignées en jaune sur la page Mentions légales.</p>') +
      card("Textes de la page Mentions légales", fText("Titre de la page", "legal.title", l.title) + fText("Titre : éditeur", "legal.editor_title", l.editor_title) +
        fText("Libellé : siège", "legal.label_address", l.label_address) + fText("Libellé : SIRET", "legal.label_siret", l.label_siret) +
        fText("Libellé : e-mail", "legal.label_email", l.label_email) + fText("Libellé : téléphone", "legal.label_phone", l.label_phone) +
        fText("Libellé : directeur de la publication", "legal.label_director", l.label_director) + fText("Titre : conception", "legal.designer_title", l.designer_title) +
        fText("Titre : hébergement", "legal.host_title", l.host_title) +
        fText("Titre : propriété intellectuelle", "legal.ip_title", l.ip_title, { wide: true }) + fText("Texte : propriété intellectuelle", "legal.ip_text", l.ip_text, { rows: 4, wide: true }) +
        fText("Titre : données personnelles", "legal.privacy_title", l.privacy_title, { wide: true }) + fText("Texte : données personnelles", "legal.privacy_text", l.privacy_text, { rows: 6, wide: true }) +
        fText("Titre : cookies", "legal.cookies_title", l.cookies_title, { wide: true }) + fText("Texte : cookies", "legal.cookies_text", l.cookies_text, { rows: 3, wide: true }) +
        '<p class="help wide">Dans les textes, {societe} est remplacé par la raison sociale et {email} par l’adresse e-mail (cliquable).</p>'),
      "mentions-legales", null, "Coordonnées et mentions");
  }

  function vTextes() {
    var u = state.content.ui, b = "ui.";
    function t(label, key, o) { return fText(label, b + key, u[key], o); }
    return "<h1>Textes communs</h1><p class=\"intro\">Libellés présents sur plusieurs pages : menu, boutons, pied de page, formulaire de contact, page d’erreur.</p>" + formWrap("form-content",
      card("Menu et barre mobile", t("Menu : accueil", "nav_home") + t("Menu : études techniques", "nav_etudes-techniques") +
        t("Menu : préparation", "nav_preparation-chantier") + t("Menu : direction de travaux", "nav_direction-travaux") +
        t("Menu : références", "nav_references") + t("Menu : bouton contact", "nav_contact") + t("Menu : administration", "nav_admin") +
        t("Barre mobile : appeler", "mobile_call") + t("Barre mobile : écrire", "mobile_write")) +
      card("Boutons et bandeaux", t("Bouton des bandeaux « appel à l’action »", "cta_button", { wide: true }) +
        t("Lien des cartes missions (accueil)", "mission_more")) +
      card("Pages missions (blocs communs)", t("Fil d’Ariane : missions", "mission_crumb") + t("Prestations : sur-titre", "mission_services_eyebrow") +
        t("Prestations : titre", "mission_services_title", { wide: true }) + t("Titre de l’objectif", "mission_objective_title") +
        t("Autres missions : sur-titre", "mission_others_eyebrow") + t("Autres missions : titre", "mission_others_title", { wide: true }) +
        t("Autres missions : libellé devant le numéro", "mission_others_label")) +
      card("Pied de page", t("Titre : missions", "footer_missions") + t("Titre : le cabinet", "footer_cabinet") +
        t("Lien : présentation", "footer_presentation") + t("Lien : références", "footer_references") + t("Lien : contact", "footer_contact_link") +
        t("Lien : mentions légales", "footer_legal") + t("Titre : contact", "footer_contact") + t("Mention de copyright", "footer_rights")) +
      card("Formulaire de contact", t("Champ : nom", "form_name") + t("Champ : entreprise", "form_company") + t("Champ : e-mail", "form_email") +
        t("Champ : téléphone", "form_phone") + t("Libellé : zone d’intervention", "contact_zone") + t("Champ : type de mission", "form_mission") +
        t("Choix vide du type de mission", "form_mission_placeholder") +
        fLines("Choix du type de mission", b + "form_missions", u.form_missions) +
        t("Champ : message", "form_message") + t("Exemple dans le champ message", "form_message_placeholder", { wide: true }) +
        t("Case de consentement", "form_consent", { rows: 2, wide: true }) + t("Lien de la case de consentement", "form_consent_link") +
        t("Bouton d’envoi", "form_submit")) +
      card("Messages du formulaire", t("Demande envoyée", "msg_ok", { wide: true }) + t("Échec de l’envoi", "msg_error", { wide: true }) +
        t("Trop de demandes", "msg_limit", { wide: true }) + t("Champs à vérifier", "msg_check", { wide: true }) +
        t("Envoi en cours", "msg_sending") + t("Connexion impossible", "msg_network", { wide: true }) +
        t("Champ obligatoire vide", "err_required") + t("Consentement manquant", "err_consent") + t("E-mail invalide", "err_email") +
        t("Téléphone invalide", "err_phone") + t("Choix invalide", "err_choice")) +
      card("Page introuvable (erreur 404)", t("Sur-titre", "notfound_eyebrow") + t("Fil d’Ariane et onglet", "notfound_crumb") +
        t("Titre", "notfound_title", { wide: true }) + t("Texte", "notfound_text", { rows: 2, wide: true }) + t("Bouton", "notfound_button")),
      "", null, "Textes communs");
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
    return '<h1>Mon compte</h1><p class="intro">Gérez votre nom, votre adresse de connexion et votre mot de passe.</p><div class="account">' +
      '<form class="edit-form" id="form-profile" novalidate>' + card("Profil",
        '<div class="fld"><label for="pf-name">Nom affiché</label><input id="pf-name" type="text" name="name" value="' + esc(u.name) + '" maxlength="120" autocomplete="name" required></div>' +
        '<div class="fld"><label for="pf-email">Adresse e-mail de connexion</label><input id="pf-email" type="email" name="email" value="' + esc(u.email) + '" maxlength="190" autocomplete="username" required></div>' +
        '<div class="fld wide" id="pf-confirm" hidden><label for="pf-cur">Mot de passe actuel</label><input id="pf-cur" type="password" name="current" autocomplete="current-password"><small class="help">Requis pour changer l’adresse de connexion.</small></div>' +
        '<div class="card-actions"><button class="btn-main" type="submit">Enregistrer le profil</button></div>') + "</form>" +
      '<form class="edit-form" id="form-password" novalidate>' + card("Mot de passe",
        '<div class="fld wide"><label for="pw-cur">Mot de passe actuel</label><input id="pw-cur" type="password" name="current" autocomplete="current-password" required></div>' +
        '<div class="fld"><label for="pw-new">Nouveau mot de passe</label><input id="pw-new" type="password" name="password" autocomplete="new-password" minlength="12" required></div>' +
        '<div class="fld"><label for="pw-conf">Confirmation</label><input id="pw-conf" type="password" name="confirm" autocomplete="new-password" required></div>' +
        '<small class="help wide">12 caractères minimum, avec des lettres et des chiffres.</small>' +
        '<div class="card-actions"><button class="btn-main" type="submit">Changer le mot de passe</button></div>') + "</form></div>";
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
      case "textes": html = vTextes(); break;
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
    view.querySelectorAll("form.edit-form").forEach(function (form) {
      if (form.id !== "form-password" && form.id !== "form-profile") {
        form.addEventListener("input", markDirty);
        form.addEventListener("change", markDirty);
      }
      form.addEventListener("submit", onSubmit);
    });
    var email = document.getElementById("pf-email");
    if (email) email.addEventListener("input", toggleProfileConfirm);
    view.querySelectorAll('input[type="password"]').forEach(addPasswordToggle);
  }
  var EYE = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>';
  function setPasswordVisible(input, btn, visible) {
    input.type = visible ? "text" : "password";
    btn.setAttribute("aria-pressed", String(visible));
    btn.setAttribute("aria-label", visible ? "Masquer le mot de passe" : "Afficher le mot de passe");
    btn.title = btn.getAttribute("aria-label");
    btn.innerHTML = EYE + (visible ? '<path d="M3 3l18 18"/>' : "") + "</svg>";
  }
  function addPasswordToggle(input) {
    var wrap = document.createElement("div");
    wrap.className = "pw-field";
    input.insertAdjacentElement("beforebegin", wrap);
    wrap.appendChild(input);
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "pw-toggle";
    wrap.appendChild(btn);
    setPasswordVisible(input, btn, false);
    btn.addEventListener("click", function () { setPasswordVisible(input, btn, input.type === "password"); });
  }
  /** Après enregistrement, les mots de passe ne restent pas affichés en clair. */
  function maskPasswords(form) {
    form.querySelectorAll(".pw-field").forEach(function (w) { setPasswordVisible(w.querySelector("input"), w.querySelector("button"), false); });
  }
  /** Le mot de passe actuel n'est demandé que si l'adresse de connexion change. */
  function toggleProfileConfirm() {
    var email = document.getElementById("pf-email").value.trim().toLowerCase();
    document.getElementById("pf-confirm").hidden = email === state.user.email;
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

    if (form.id === "form-profile") {
      var pf = form.elements;
      lock(form, true);
      api("account/profile", { name: pf.name.value, email: pf.email.value, current: pf.current.value }).then(function (r) {
        setCsrf(r.csrf);
        state.user = r.user;
        pf.name.value = r.user.name; pf.email.value = r.user.email; pf.current.value = "";
        maskPasswords(form);
        toggleProfileConfirm();
        document.querySelector(".top-actions .user").textContent = r.user.name || r.user.email;
        toast("Profil mis à jour.");
      }).catch(function (err) { toast(err.message, "error"); }).then(function () { lock(form, false); });
      return;
    }

    if (form.id === "form-password") {
      var f = form.elements;
      if (f.password.value !== f.confirm.value) { toast("La confirmation ne correspond pas au nouveau mot de passe.", "error"); return; }
      lock(form, true);
      api("account/password", { current: f.current.value, password: f.password.value }).then(function (r) {
        setCsrf(r.csrf);
        form.reset(); maskPasswords(form); toast("Mot de passe modifié.");
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

    // Outils de maintenance
    if ((t = e.target.closest("[data-tool]"))) {
      var tool = t, kind = tool.getAttribute("data-tool");
      tool.disabled = true;
      api("tools/" + kind, {}).then(function (r) {
        toast(kind === "minify" ? "Fichiers allégés regénérés." : r.images + " images traitées, " + r.webp + " copies WebP créées.");
      }).catch(function (err) { toast(err.message, "error"); }).then(function () { tool.disabled = false; });
      return;
    }

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
