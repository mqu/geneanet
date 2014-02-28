geneanet
========

php classes for Geneanet web site reading (genealogy)


utilisation :

- 1 : télécharger les sources,
- 2 : configurer l'application : copier config.ini.default sur config.ini, ou mieux sur $HOME/.config/geneanet.ini
- 3.1 : completer par le login et MDP du site geneanet,
- 3.2 : le site généanet doit fonctionner en "anglais" : choisir la langue anglaise en haut de la page du site.
- 4 : selectionner une page que l'on souhaite télécharger ; j'ai choisi celle-ci : 'http://gw.geneanet.org/xxxx?lang=en&pz=individu&nz=confidentiel&ocz=15&p=yyyyy&n=zzzzzz'
- 5 : activer le script principal : php -q test-gedcomwriter.php $page > nom-fichier.gedcom
- 6 : 3 modes de fonctionnement pour le grabber (ligne 33): single, ascendant, descendant.
- 

