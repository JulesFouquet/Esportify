# Esportify

-   Description

Esportify est une plateforme de gestion d'événements e-sport.  
Elle permet aux utilisateurs de consulter, proposer, s’inscrire à des événements, et d’échanger via un chat dédié.  
Le projet utilise à la fois une base de données relationnelle et une base non relationnelle pour optimiser la gestion des données.

---

**Site en ligne** : [https://esportify-ecf-29509a78ee60.herokuapp.com/]
pour vous connecter, créer un compte !

---

Comment deployer le serveur symfony depuis son pc en local

-   Prérequis

-   PHP >= 8.1
-   Composer
-   MySQL

---

-   Installation & déploiement local

1. **Cloner le dépôt**

dans bash ->
git clone https://github.com/JulesFouquet/esportify.git
cd esportify

2. **Installer les dépendances PHP**

composer install

3. **Configurer l’environnement**

Avoir PHPMyAdmin
Dans .env, pour MySQL
Adapter en remplacent root:root et esportify par vos identifiants et nom de BDD exemple : esportifyTest.
-> DATABASE_URL="mysql://root:root@127.0.0.1:3306/esportifyTest_db?serverVersion=8.0"

4. **Créer la base de données relationnelle**

php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate

5. **Charger les données de test**

php bin/console doctrine:fixtures:load

Cela va créer automatiquement :
Un Admin → admin@esportify.com / adminpass
Un Organisateur → orga@esportify.com / orgapass
Un User → user@esportify.com / userpass
Quelques événements liés à l'organisateur

l'event creer sera visible car déjà validé par l'admin et l'orga, si vous voulez
créer d'autre event il faudra les faire validés par un compte orgnisateur puis par un compte admin

6. **Lancer le serveur Symfony**

symfony server:start

7. **Lancer le serveur Symfony**

Ouvre ton navigateur et va sur http://localhost:8000

---

Ajout :

-   Manuel d’utilisation disponible dans /docs/Manuel_Utilisation_Esportify.pdf
