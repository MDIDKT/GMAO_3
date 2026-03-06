# Ameliorations — De MVP a Produit Complet

> Ce document liste les ameliorations a apporter pour passer du MVP actuel a un SaaS distribue.
> Classees par priorite : P1 = indispensable avant production, P2 = important, P3 = nice-to-have.

---

## P1 — Indispensable avant mise en production

### 1. Tests automatises
**Pourquoi :** Sans tests, chaque modification risque de casser quelque chose sans qu'on le sache.

**A faire :**
- Tests unitaires (PHPUnit) sur les services : NumberingService, InterventionService
- Tests fonctionnels sur les controllers : verifier les redirections, les 403, les flash messages
- Tests de regression : quand tu corriges un bug, ecris un test qui reproduit le bug d'abord

**Outils :** `composer require --dev phpunit/phpunit symfony/test-pack`

---

### 2. Numerotation transactionnelle
**Pourquoi :** Si 2 personnes creent une demande au meme instant, elles pourraient obtenir le meme numero.

**Solution :** Creer une table `counter` avec un verrou en base :
```sql
CREATE TABLE counter (
    prefix VARCHAR(10) PRIMARY KEY,
    year INT,
    last_value INT DEFAULT 0
);
-- Utiliser SELECT ... FOR UPDATE dans le service
```

---

### 3. Validation cote entite (Assert)
**Pourquoi :** Les contraintes dans les FormTypes ne protegent que les formulaires web. Si on cree une API plus tard, il n'y a aucune validation.

**A faire :** Ajouter des contraintes Assert sur les entites :
```php
use Symfony\Component\Validator\Constraints as Assert;

#[Assert\NotBlank]
#[Assert\Length(max: 255)]
private ?string $titre = null;

#[Assert\Email]
private ?string $email = null;
```

---

### 4. HTTPS + Headers de securite
**Pourquoi :** Sans HTTPS, les mots de passe circulent en clair. Sans headers CSP, l'app est vulnerable aux attaques XSS.

**A faire :**
- Forcer HTTPS dans security.yaml ou via le reverse proxy
- Ajouter les headers : Content-Security-Policy, X-Frame-Options, X-Content-Type-Options
- Installer NelmioSecurityBundle : `composer require nelmio/security-bundle`

---

### 5. Rate limiting sur /login
**Pourquoi :** Sans limitation, un attaquant peut tester des milliers de mots de passe par minute.

**Solution :** Symfony RateLimiter :
```yaml
# config/packages/rate_limiter.yaml
framework:
    rate_limiter:
        login:
            policy: sliding_window
            limit: 5
            interval: '1 minute'
```

---

### 6. Build CSS avec Vite (remplacer le CDN Tailwind)
**Pourquoi :** Le CDN Tailwind charge TOUT Tailwind (3 Mo) alors que ton app n'utilise que 50 Ko. En production, c'est trop lent.

**A faire :**
```bash
composer require pentatrion/vite-bundle
npm install tailwindcss @tailwindcss/vite
```

---

## P2 — Important pour un produit serieux

### 7. Audit trail (Event Log)
**Pourquoi :** En maintenance, il faut savoir QUI a change le statut, QUAND, et depuis QUEL statut.

**Solution :** Creer une entite `AuditLog` :
- action (string : "statut_change", "creation", "suppression")
- entite (string : "Demande", "Intervention")
- entiteId (int)
- ancienneValeur / nouvelleValeur
- user (ManyToOne)
- createdAt

Enregistrer dans InterventionService a chaque transition.

---

### 8. Notifications email
**Pourquoi :** Le technicien devrait recevoir un email quand une intervention lui est assignee. Le demandeur devrait etre prevenu quand sa demande est cloturee.

**A faire :**
- Evenements Symfony (EventDispatcher) pour decoupler
- Templates email Twig pour chaque notification
- Configurer un vrai SMTP (Mailgun, SendGrid, ou SMTP Cloudways)

---

### 9. Export PDF / Excel
**Pourquoi :** Les planificateurs veulent imprimer les rapports ou les envoyer par email.

**Outils :**
- PDF : `dompdf/dompdf` ou `knplabs/knp-snappy-bundle`
- Excel : `phpoffice/phpspreadsheet`

---

### 10. API REST
**Pourquoi :** Pour connecter une app mobile ou un autre systeme.

**Solution :** API Platform (le standard Symfony) :
```bash
composer require api
```
Ajouter `#[ApiResource]` sur les entites. API Platform genere automatiquement les endpoints REST + la documentation Swagger.

---

### 11. SLA et alertes retard
**Pourquoi :** En GMAO reelle, une demande P1 doit etre traitee en 4h. Si ce delai est depasse, une alerte doit etre envoyee.

**A faire :**
- Ajouter une table `sla_config` : priorite, delai_heures
- Commande Symfony (CRON) qui verifie les depassements toutes les heures
- Envoi email au planificateur si retard detecte

---

### 12. Recherche avancee (fulltext)
**Pourquoi :** Avec 1000+ demandes, la recherche par LIKE '%mot%' devient lente.

**Solutions :**
- MySQL FULLTEXT index (simple, pas de dependance)
- Elasticsearch / Meilisearch (plus puissant, recherche floue)

---

## P3 — Nice-to-have

### 13. Mode sombre (Dark Mode)
Tailwind supporte `dark:` nativement. Ajouter un toggle dans le layout.

### 14. Tableaux de bord personnalisables
Permettre aux planificateurs de choisir quels KPI afficher. Stocker les preferences en base (JSON).

### 15. Drag & drop pour le calendrier interventions
Librairie JS type FullCalendar pour planifier visuellement les interventions.

### 16. QR Code sur les equipements
Generer un QR code par equipement. Le technicien scanne avec son telephone pour acceder directement a la fiche.

### 17. Multi-langue (i18n)
Symfony supporte nativement l'internationalisation. Utiliser les fichiers de traduction `translations/messages.fr.yaml`.

### 18. PWA (Progressive Web App)
Transformer l'app web en PWA pour un acces hors-ligne basique et une icone sur le telephone.

---

## Ordre de priorite recommande

| Phase | Ameliorations | Effort estime |
|-------|--------------|---------------|
| **Pre-production** | Tests auto + HTTPS + Rate limit + Build Vite + Assert | 3-5 jours |
| **V1.1** | Audit trail + Notifications email + Numerotation transactionnelle | 3-4 jours |
| **V1.2** | API REST + Export PDF/Excel + SLA | 5-7 jours |
| **V2.0** | Multi-tenant reel (super-admin) + Recherche avancee + Calendrier | 10-15 jours |
