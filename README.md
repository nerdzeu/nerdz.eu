NERDZ
=====
*A mix between a Social Network and a forum*


This is the code repository for [nerdz.eu](http://www.nerdz.eu).

About
=====
Nerdz is a mix between a forum and a social network. To understand what NERDZ really is, the easily way is to go on [nerdz.eu](http://www.nerdz.eu) and enjoy the experience.

Development
===========

There is a lot of work to do:

*  Move template to separate repositories: so designers can fork only the template and work on it.
*  Improve the User Experience. So work on the design of template located in `tpl/`.
*  Create a file containing associations between template number and name, and...
*  Create option to make possible to switch template in User preference.
*  Create REST APIs (in a new repo. And API will be written in TypeScript + node.js with OAuth2 ) and create necessary tables and options in this repo.
*  On [nerdz.eu](http://www.nerdz.eu), once the API are ready, I reverse proxy nodeserver to api.nerdz.eu:80, so we can finally use API!
*  And more...


In this repo temporary files and C execuptables for stats are exclused.

Contributing
============
You're welcome contribute via pull requests; only via pull requests.

Before to send a pull request, you want to setup your local version of nerdz, thus you can (quite easily) have your copy of NERDZ following the instructions in `setup/README.md`

Get it
======
To get a complete version of nerdz (including all templates, that are submodules) do:
```bash
git clone --recursive git://github.com/nerdzeu/nerdz.eu.git
```


[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/nerdzeu/nerdz.eu/trend.png)](https://bitdeli.com/free "Bitdeli Badge")

