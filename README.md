geneanet
========

php classes for Geneanet web site reading (genealogy)


utilisation :

- 1 : télécharger les sources,
- 2 : configurer l'application : copier config.ini.default sur config.ini, ou mieux sur $HOME/.config/geneanet.ini
- 3 : completer par le login et MDP du site geneanet,
- 4 : le site généanet doit fonctionner en "anglais" : choisir la langue anglaise en haut de la page du site.
- 5 : selectionner une page que l'on souhaite télécharger ; j'ai choisi celle-ci : 
 
url='http://gw.geneanet.org/xxxx?lang=en&pz=individu&nz=confidentiel&ocz=15&p=yyyyy&n=zzzzzz'

- 6 : activer le script principal : 

  php -q test-gedcomwriter.php $page > nom-fichier.gedcom

- 7 : 3 modes de fonctionnement pour le grabber (ligne 33): single, ascendant, descendant.


