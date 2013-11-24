ORGANIZZAZIONE FILES/DIRECTORY
==============================

Ogni template dovrà essere organizzato secondo la seguente struttura dei files/directory:

`./base`: [la cartella contiene le pagine "di base" e generiche, ogni file in questa cartella viene disegnato al momento del richiamo del file .php associato conf lo stesso nome. Esempio: about.html, viene disegnato andando su /about.php]

> about.html     error.html   header.html  rank.html      reset.html  stats.html

> codelist.html  footer.html  images       register.html  share.html  terms.html

`./base/images`: [cartella contenente le immagini per i file di base, organizzati per on/offline]
offline  online

`./base/images/offline`:
> chromeicon.jpeg  firefoxicon.jpeg

`./base/images/online`:
> chatofficon.png  chatonicon.png  ladyicon.png

`./css`: [la cartella contiene i css. Per specificare in che pagina incluederli vedere: `template.values`]

`./home`: [i file dell'homepage utenti registrati, layout e lista profili (in chiamata ajax)]
> images  layout.html  postlist.html

`./home/images`:
> lock.png  rss.gif  unlock.png

`./js`: [la cartella contiene i files javascript. Per specificare in che pagina incluederli vedere: `template.values`]

`./pm`: [cartella dei pm]
> conversation.html  form.html  inbox.html  main.html

`./preferences`: [Esistente solo per il template di default. Negli altri non è esistente per motivi di sicurezza]
> account.html  friends.html  language.html  profile.html

> delete.html   guests.html   layout.html    projects.html

`./profile`:
> blacklisted.html  comments.html  layout.html  postlist.html

> closed.html       images         post.html    postnotfound.html

`./profile/images`:

`./project`: [cartella dei progetti]
> closed.html  layout.html  private.html

FILE: template.values
=====================
Permette di specificare i file javascript e css da incluedere nelle differenti pagine, cioè in:
> codelist.php, error.php, faq.php, home.php, index.php, informations.php, list.php, pm.php

> preferences.php, profile.php, project.php, rank.php, reset.php, share.php, stats.php, terms.php

@In particolare notare che è possible aggiungere nelle due sezioni css e javascript, il campo:
`default: css/css di default.css;` (equivalentemente per js)

Ed il seguente file verrà incluso in ogni pagina sopra citata, in ogni caso prima degli altri.
Così facendo è possibile ad esempio definire regole css generali, nel file di default e poi a seconda
della pagina in questione, ridefinirle in altri file o modificarle lievemente.

API JAVASCRIPT
==============
Sono a disposizione API JS per una facile creazione dei template.
(quasi) Sempre incluso jquery in ogni pagina, sempre aggiornato all'ultima versione.


Scrittura dei file
=================
Per la scrittura dei file .html, presenti nelle varie directory, vedere la guida a RAINTPL.
Le variabili utilizzabili nei vari files, si trovano in `nomefile.vars`
(che è presente nella stessa cartella in cui si trova anche `nomefile.html` che è la pagina d'esempio da usare nella stesura della nuova

CSS
===
Obbligatoria la class "quote", che è la classe css che verrà attribuita ai tag:

[quote=username]testo[/quote]

Quindi inserirla o in un css associato di default ad ogni file, oppure solo alle sole pagine in cui verrà usato quel tag (home, profile, project)

Obbligatoria la class `spoiler`, che è la classe css che verrà attribuita ai tag:

[spoiler]text nascoste[/spoiler]

Quindi inserirla o in un css associato di default ad ogni file, oppure solo alle sole pagine in cui verrà usato quel tag (home, profile, project)

Obbligatoria la class `.img_frame`, che è la classe CSS che sarà attribuita alle miniature delle immagini ([img]url[/img])

Obbligatoria la class `.img_frame-extended`, che è la classe CSS attribuita alle immagini ingrandite. Tendenzialmente elimina le limitazioni di dimensione delle miniature

