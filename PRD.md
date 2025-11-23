# Product Requirements Document (PRD)

## Nova Protocol

### Présentation générale

Jeu en tour par tour où chaque joueur ne peut réaliser qu'une seule
action offensive par tour. Le terrain comporte trois positions : 1
(proche), 2 (milieu), 3 (éloigné).

---

## 1. Classes du jeu

### 1.1 Classe Vaisseau

**Attributs** - position : valeurs possibles (1, 3), départ à 2 - vie :
1000 par défaut - puissance de tir : 100 par défaut - liste de drones :
2 drones reconnaissance + 1 drone attaque par défaut - magicienActuel :
magicien du vaisseau (puissance 1 par défaut)

**Méthodes** - **deplacer()**\
Déplacement de +1 ou -1 uniquement.\
Depuis x=2 : peut aller en 1 ou 3.\
Depuis x=1 : peut seulement aller en 2.\
Après un déplacement, le joueur peut encore agir (sauf se redéplacer).

- **tirer()**\
  Inflige des dégâts basés sur la puissance de tir, modifiés par la
  distance :

  - attaquant en x=3 : dégâts ×1.5\
  - attaquant en x=2 : dégâts ×1\
  - attaquant en x=1 : dégâts ×0.75\
    Le défenseur en x=1 ne reçoit que 0.75× les dégâts prévus.

- **lancerDrone()**\
  Le joueur choisit un drone d'attaque ou de reconnaissance.

  - **Drone d'attaque :**
    - 50% buff ×1.5 sur la prochaine attaque
    - 20% aucun effet
    - 30% buff ×2 sur la prochaine attaque
  - **Drone de reconnaissance :**
    - 33% trouver un magicien puissance 2 à 5 (uniforme)
    - 33% trouver un canon puissance 80 à 170 (uniforme)
    - 33% trouver une étoile de soin soignant 1/10 de la vie\
      Si la vie est déjà pleine : suppression de la proba de soin,
      probas deviennent 50/50.

- **magie()**\
  Lance un sort basé sur la puissance du magicien.\
  Après usage, le magicien perd tout son mana et ne peut plus lancer
  de sort tant que sa mana n'est pas restaurée.

  **Pour un magicien puissance 1 :**

  - Exécution si l'ennemi ≤ 500 PV (seuil × puissance du magicien).\
  - 33% sort de poison : 50 dégâts par tour jusqu'à la fin du jeu
    (dégâts × puissance).\
  - 33% sort de soin : +100 PV à chaque tour du vaisseau (soins ×
    puissance).\
  - 33% sort de paralysie : 1 chance sur 5 de bloquer l'action
    adverse durant 3 tours (probabilité multipliée par
    puissance/5).\
    Un seul effet possible sur un vaisseau adverse.

- **recharger()**\
  Remet la mana du magicien à fond et ajoute 1 drone reconnaissance +
  1 drone attaque.

---

### 1.2 Classe Drone

**Attributs** - type : attaque ou reconnaissance

**Méthodes** - **agir()** : applique les probabilités associés à son
type.

---

### 1.3 Classe Magicien

**Attributs** - nom - mana : 1 - puissance

**Méthodes** - agir() - méditer()

---

## 2. Terrain de jeu

Trois positions : éloigné (3), milieu (2), proche (1).\
Déplacement toujours possible, suivable d'une action offensive.

---

## 3. Concept du jeu

- Début du jeu : choix d'un vaisseau parmi 10, aux caractéristiques
  différentes.\
- Vaisseau : 1000 PV.\
- Attaque de base : 100 dégâts.\
- Dégâts diminuent de 20 si on tire plusieurs tours d'affilée.\
- Après un tour sans tirer : +20 dégâts, jusqu'à la valeur max.\
- Cinq actions disponibles : attaquer, se déplacer, magie, drone,
  recharger.
- Les drones sont à usage unique.\
  Recharger donne 2 nouveaux drones.\
- Un magicien trouvé en reconnaissance remplace l'ancien (un seul
  magicien possible).\
- **Effets des zones :**
  - Éloigné : forte attaque mais nécessite un tour de recharge.
  - Milieu : attaque normale.
  - Proche : dégâts infligés réduits mais dégâts reçus réduits.

---

## 4. Détails des actions

### 4.1 Attaquer

Tir direct sur le vaisseau adverse, dégâts modifiés par la zone.

### 4.2 Se déplacer

Déplacement d'une case, puis action possible ensuite.

### 4.3 Utilisation des drones

- Reconnaissance : chance d'obtenir magicien, canon, soin.
- Attaque : chance de trouver une faille qui multiplie les dégâts de
  la prochaine attaque.

### 4.4 Magie

Utilisée par un magicien.\
Le magicien par défaut est faible ; un meilleur peut être trouvé via
reconnaissance.\
Les sorts peuvent infliger des malus : immobilisation, dégâts par tour,
etc.

---

## 5. Parcours utilisateur

1.  Arrivée sur la page du jeu.\
    Choix joueur 1 ou 2, attente de connexion du second joueur.
2.  Une fois les deux joueurs connectés : écran de sélection parmi 10
    vaisseaux.\
    Interface de sélection via flèches gauche/droite.
3.  Les deux joueurs valident : début de partie.
4.  Pile ou face pour déterminer qui commence.
5.  Actions possibles :
    - Attaquer
    - Se déplacer (choix gauche/droite)
    - Lancer un sort magique\
      Sous-menu : puissance maximale (toute la mana) ou puissance
      limitée (moitié)
    - Lancer un drone\
      Sous-menu listant les drones restants
    - Recharger (mana + drones)
6.  Le jeu se termine lorsqu'un vaisseau tombe à 0 PV ou moins.\
    Affichage victoire/défaite.

---

## 6. Les 10 vaisseaux de départ

1.  Plus de PV : 1200\
2.  Meilleur magicien au début\
3.  Ses attaques font (incomplet dans le document original)
