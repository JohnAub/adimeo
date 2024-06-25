# README

## Modules Personnalisés pour la Gestion des Événements

Ce package contient deux modules personnalisés : `event_block` et `event_depublish`. Ces modules sont conçus pour améliorer la gestion des événements sur votre site Drupal en affichant des événements connexes sur la page de détail d'un événement et en dépubliant automatiquement les événements expirés.

### Modules Inclus

1. **event_block**
    - Affiche un bloc personnalisé sur la page de détail d'un événement.
    - Ce bloc montre trois autres événements du même type (taxonomie) que l'événement courant.
    - Les événements sont ordonnés par date de début en ordre croissant.
    - Seuls les événements dont la date de fin n'est pas dépassée sont affichés.
    - Si moins de trois événements du même type sont trouvés, le bloc est complété avec des événements d'autres types, également ordonnés par date de début et en veillant à ce que leur date de fin n'est pas dépassée.

2. **event_depublish**
    - Implémente une tâche cron pour dépublier les événements dont la date de fin est dépassée.
    - Utilise un QueueWorker pour des performances optimales, permettant de traiter un grand nombre d'événements efficacement.

