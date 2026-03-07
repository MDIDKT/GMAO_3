#!/usr/bin/env python3
"""
Génère PLAN-AMELIORATIONS.pdf dans le style de GMAO_MVP_Spec_v1_2.pdf
Couleurs, headers, code blocks, tableaux identiques au document de référence.
"""

from reportlab.lib.pagesizes import A4
from reportlab.lib.units import cm
from reportlab.lib.colors import HexColor, white, black
from reportlab.lib.styles import ParagraphStyle
from reportlab.lib.enums import TA_LEFT, TA_CENTER
from reportlab.platypus import (
    SimpleDocTemplate, Paragraph, Spacer, Table, TableStyle,
    HRFlowable, KeepTogether, PageBreak, Flowable
)

# ─── COULEURS (calquées sur GMAO_MVP_Spec_v1_2.pdf) ────────────────────────
C_BLUE        = HexColor('#1a56db')   # Titres sections
C_BLUE_DARK   = HexColor('#1e3a5f')   # Fond tableaux / phase 1
C_BLUE_LIGHT  = HexColor('#ebf3fb')   # Fond boîtes info
C_RED         = HexColor('#dc2626')   # Piège courant
C_GREEN       = HexColor('#15803d')   # Validation
C_CODE_BG     = HexColor('#f5f5f5')   # Fond blocs code
C_CODE_KW     = HexColor('#dc2626')   # Mots-clés rouges
C_CODE_FUNC   = HexColor('#2563eb')   # Fonctions bleues
C_CODE_CMT    = HexColor('#6b7280')   # Commentaires gris
C_CODE_STR    = HexColor('#16a34a')   # Strings vertes
C_GRAY        = HexColor('#e5e7eb')   # Lignes séparateurs
C_GRAY_DARK   = HexColor('#374151')   # Texte corps
C_INFO_BG     = HexColor('#eff6ff')   # Fond boîte info
C_INFO_BORDER = HexColor('#3b82f6')   # Bordure boîte info
C_WARN_BG     = HexColor('#fff7ed')   # Fond boîte avertissement
C_WARN_BORDER = HexColor('#ea580c')   # Bordure boîte avertissement
C_VALID_BG    = HexColor('#f0fdf4')   # Fond boîte validation
C_VALID_BDR   = HexColor('#16a34a')   # Bordure boîte validation

# Couleurs par phase : (fond header phase, fond header étape, couleur texte étape)
PHASE_COLORS = {
    1: (HexColor('#1e3a5f'), HexColor('#dbeafe'), HexColor('#1e40af')),
    2: (HexColor('#064e3b'), HexColor('#d1fae5'), HexColor('#065f46')),
    3: (HexColor('#7c2d12'), HexColor('#fee2e2'), HexColor('#991b1b')),
    4: (HexColor('#4c1d95'), HexColor('#ede9fe'), HexColor('#5b21b6')),
    5: (HexColor('#1f2937'), HexColor('#f3f4f6'), HexColor('#374151')),
}

PAGE_W, PAGE_H = A4
MARGIN = 2 * cm
DOC_W  = PAGE_W - 2 * MARGIN


# ─── STYLES ─────────────────────────────────────────────────────────────────
def S(name, **kw):
    """Crée un ParagraphStyle rapide."""
    base = {
        'fontName': 'Helvetica',
        'fontSize': 9.5,
        'textColor': C_GRAY_DARK,
        'leading': 14,
        'spaceAfter': 3,
    }
    base.update(kw)
    return ParagraphStyle(name, **base)

ST = {
    'big_title': S('big_title', fontName='Helvetica-Bold', fontSize=26, textColor=black,
                   leading=32, spaceAfter=6),
    'big_sub':   S('big_sub',   fontName='Helvetica', fontSize=14, textColor=C_GRAY_DARK,
                   leading=20, spaceAfter=16),
    'h1':        S('h1',  fontName='Helvetica-Bold', fontSize=15, textColor=C_BLUE,
                   leading=20, spaceBefore=14, spaceAfter=4),
    'h2':        S('h2',  fontName='Helvetica-Bold', fontSize=11, textColor=C_BLUE,
                   leading=16, spaceBefore=8, spaceAfter=3),
    'body':      S('body'),
    'bold':      S('bold', fontName='Helvetica-Bold'),
    'italic':    S('italic', fontName='Helvetica-Oblique', textColor=C_GRAY_DARK),
    'code_line': S('code_line', fontName='Courier', fontSize=8, leading=12,
                   textColor=C_GRAY_DARK, spaceAfter=0),
    'validation':S('validation', fontName='Helvetica-Bold', fontSize=9.5,
                   textColor=C_GREEN, leading=14, spaceAfter=6),
    'warn_text': S('warn_text', fontName='Helvetica-BoldOblique', fontSize=8.8,
                   textColor=HexColor('#9a3412'), leading=13, spaceAfter=4),
    'tbl_hdr':   S('tbl_hdr', fontName='Helvetica-Bold', fontSize=9.5,
                   textColor=white, alignment=TA_CENTER, leading=14),
    'tbl_cell':  S('tbl_cell', fontName='Helvetica', fontSize=9, textColor=C_GRAY_DARK, leading=13),
    'toc_item':  S('toc_item', fontName='Helvetica', fontSize=10, textColor=C_GRAY_DARK,
                   leading=16, spaceAfter=2, leftIndent=8),
}


# ─── FLOWABLES CUSTOM ───────────────────────────────────────────────────────
class ColorBox(Flowable):
    """Boîte colorée avec bordure gauche (info, warn, validation)."""
    def __init__(self, paragraphs, bg=C_INFO_BG, border=C_INFO_BORDER, width=None):
        super().__init__()
        self.paragraphs = paragraphs  # liste de Paragraph
        self.bg = bg
        self.border = border
        self.width = width or DOC_W
        self._h = 0

    def wrap(self, aW, aH):
        w = min(self.width, aW)
        h = 10  # padding top
        for p in self.paragraphs:
            _, ph = p.wrap(w - 22, aH)
            h += ph + 4
        h += 6  # padding bottom
        self._h = h
        self._w = w
        return w, h

    def draw(self):
        c = self.canv
        w, h = self._w, self._h
        c.saveState()
        c.setFillColor(self.bg)
        c.roundRect(0, 0, w, h, 4, fill=1, stroke=0)
        c.setFillColor(self.border)
        c.rect(0, 0, 4, h, fill=1, stroke=0)
        y = h - 10
        for p in self.paragraphs:
            _, ph = p.wrap(w - 22, 2000)
            y -= ph
            p.drawOn(c, 12, y)
            y -= 4
        c.restoreState()


class PhaseHeader(Flowable):
    """Bandeau coloré de phase (PHASE 1, 2, …)."""
    def __init__(self, num, title, duration, objective, width=None):
        super().__init__()
        self.num = num
        self.title = title
        self.duration = duration
        self.objective = objective
        self.width = width or DOC_W
        self.bg, _, _ = PHASE_COLORS.get(num, (C_BLUE_DARK, C_BLUE_LIGHT, C_BLUE))

    def wrap(self, aW, aH):
        return min(self.width, aW), 56

    def draw(self):
        c = self.canv
        w, h = min(self.width, DOC_W), 56
        c.saveState()
        c.setFillColor(self.bg)
        c.roundRect(0, 0, w, h, 5, fill=1, stroke=0)
        c.setFont('Helvetica-Bold', 13)
        c.setFillColor(white)
        c.drawString(14, h - 22, self.title)
        c.setFont('Helvetica-Oblique', 8.5)
        c.setFillColor(HexColor('#d1d5db'))
        sub = f'Durée estimée : {self.duration}   ·   Objectif : {self.objective}'
        c.drawString(14, 12, sub[:120])
        c.restoreState()


class StepHeader(Flowable):
    """Bandeau coloré d'étape (Étape 1.1, 1.2, …)."""
    def __init__(self, step_id, title, phase=1, width=None):
        super().__init__()
        self.step_id = step_id
        self.title = title
        self.phase = phase
        self.width = width or DOC_W
        _, self.bg, self.fg = PHASE_COLORS.get(phase, (C_BLUE_DARK, C_BLUE_LIGHT, C_BLUE))

    def wrap(self, aW, aH):
        return min(self.width, aW), 30

    def draw(self):
        c = self.canv
        w, h = min(self.width, DOC_W), 30
        c.saveState()
        c.setFillColor(self.bg)
        c.roundRect(0, 0, w, h, 3, fill=1, stroke=0)
        c.setFont('Helvetica-Bold', 10.5)
        c.setFillColor(self.fg)
        c.drawString(12, 10, f'{self.step_id} — {self.title}')
        c.restoreState()


class CodeBlock(Flowable):
    """Bloc de code avec fond gris et police Courier."""
    def __init__(self, lines, width=None):
        super().__init__()
        self.lines = lines if isinstance(lines, list) else lines.strip().split('\n')
        self.width = width or DOC_W
        self._h = 0

    def wrap(self, aW, aH):
        w = min(self.width, aW)
        h = 10 + len(self.lines) * 11 + 8
        self._h = h
        self._w = w
        return w, h

    def draw(self):
        c = self.canv
        w, h = self._w, self._h
        c.saveState()
        c.setFillColor(C_CODE_BG)
        c.roundRect(0, 0, w, h, 3, fill=1, stroke=0)
        c.setStrokeColor(C_GRAY)
        c.setLineWidth(0.4)
        c.roundRect(0, 0, w, h, 3, fill=0, stroke=1)
        y = h - 10
        for line in self.lines:
            y -= 11
            self._draw_line(c, line, 8, y, w - 16)
        c.restoreState()

    def _draw_line(self, c, line, x, y, max_w):
        stripped = line.strip()
        MAX = int(max_w / 5.2)
        txt = line[:MAX]
        if stripped.startswith('#') or stripped.startswith('//'):
            c.setFillColor(C_CODE_CMT)
        elif stripped.startswith('SELECT') or stripped.startswith('ALTER'):
            c.setFillColor(C_CODE_FUNC)
        else:
            c.setFillColor(C_GRAY_DARK)
        c.setFont('Courier', 7.5)
        c.drawString(x, y, txt)


# ─── HELPERS ────────────────────────────────────────────────────────────────
def hr():
    return HRFlowable(width=DOC_W, thickness=0.5, color=C_GRAY, spaceAfter=8, spaceBefore=4)

def sp(h=6):
    return Spacer(1, h)

def p(text, style='body'):
    return Paragraph(text, ST[style])

def bold(text):
    return Paragraph(text, ST['bold'])

def item(text, indent=12):
    s = S('_item', leftIndent=indent, spaceAfter=2, leading=14)
    return Paragraph(f'● &nbsp;{text}', s)

def sub_item(text):
    s = S('_sub', leftIndent=24, spaceAfter=1, leading=13, fontSize=9)
    return Paragraph(f'○ &nbsp;{text}', s)

def num(n, text):
    s = S('_num', leftIndent=8, spaceAfter=3, leading=14)
    return Paragraph(f'<b>{n}.</b> &nbsp;{text}', s)

def validation(text):
    return Paragraph(
        f'✓ <b>Critère de validation :</b> {text}',
        ST['validation']
    )

def piege(text):
    return ColorBox(
        [Paragraph(f'■ <i>Piège courant : {text}</i>', ST['warn_text'])],
        bg=C_WARN_BG, border=C_WARN_BORDER
    )

def info_box(bold_label, text):
    return ColorBox([
        Paragraph(f'<b>{bold_label}</b>', ST['bold']),
        Paragraph(text, ST['body']),
    ], bg=C_INFO_BG, border=C_INFO_BORDER)

def code(lines):
    return CodeBlock(lines)

def make_table(rows, widths=None, hdr_color=None):
    hc = hdr_color or C_BLUE_DARK
    if widths is None:
        n = len(rows[0])
        widths = [DOC_W / n] * n
    t = Table(rows, colWidths=widths)
    t.setStyle(TableStyle([
        ('BACKGROUND',   (0, 0), (-1, 0),  hc),
        ('TEXTCOLOR',    (0, 0), (-1, 0),  white),
        ('FONTNAME',     (0, 0), (-1, 0),  'Helvetica-Bold'),
        ('FONTSIZE',     (0, 0), (-1, 0),  9.5),
        ('ALIGN',        (0, 0), (-1, 0),  'CENTER'),
        ('VALIGN',       (0, 0), (-1, -1), 'MIDDLE'),
        ('FONTNAME',     (0, 1), (-1, -1), 'Helvetica'),
        ('FONTSIZE',     (0, 1), (-1, -1), 9),
        ('ROWBACKGROUNDS',(0,1), (-1, -1), [white, HexColor('#f8fafc')]),
        ('GRID',         (0, 0), (-1, -1), 0.4, C_GRAY),
        ('LEFTPADDING',  (0, 0), (-1, -1), 8),
        ('RIGHTPADDING', (0, 0), (-1, -1), 8),
        ('TOPPADDING',   (0, 0), (-1, -1), 6),
        ('BOTTOMPADDING',(0, 0), (-1, -1), 6),
    ]))
    return t


# ─── HEADER / FOOTER ────────────────────────────────────────────────────────
def draw_header_footer(canvas, doc):
    canvas.saveState()
    w, h = A4
    canvas.setStrokeColor(C_GRAY)
    canvas.setLineWidth(0.5)
    # Header
    canvas.line(MARGIN, h - 1.4*cm, w - MARGIN, h - 1.4*cm)
    canvas.setFont('Helvetica', 7.5)
    canvas.setFillColor(C_GRAY_DARK)
    canvas.drawString(MARGIN, h - 1.2*cm, "Plan d'Améliorations — GMAO MVP vers Production")
    canvas.drawRightString(w - MARGIN, h - 1.2*cm, 'v1.0 — 12/02/2026')
    # Footer
    canvas.line(MARGIN, 1.4*cm, w - MARGIN, 1.4*cm)
    canvas.drawCentredString(w / 2, 0.9*cm, f'Page {doc.page}')
    canvas.restoreState()


# ─── CONTENU ────────────────────────────────────────────────────────────────
def build():
    story = []

    # ══════════════════════════════════════════════════════════════════════════
    # PAGE DE TITRE
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        sp(28),
        p("Plan d'Améliorations", 'big_title'),
        p('GMAO MVP vers Production', 'big_sub'),
        hr(),
        sp(10),
        ColorBox([
            Paragraph("<b>Objectif :</b> Transformer le MVP en application solide, testée et déployable. "
                      "<b>Durée totale estimée :</b> 25-35 jours de travail", ST['body']),
            Paragraph("<b>Prérequis :</b> MVP Jour 0-19 valide, tests manuels OK", ST['body']),
        ], bg=C_INFO_BG, border=C_INFO_BORDER),
        sp(24),
        p('Table des matières', 'h1'),
        hr(),
    ]
    toc = [
        ('PHASE 1', 'Solidifier les Fondations', '5-7 jours'),
        ('PHASE 2', 'Fonctionnalités Métier', '7-10 jours'),
        ('PHASE 3', 'API et Ouverture', '5-7 jours'),
        ('PHASE 4', 'Interface et Expérience', '5-7 jours'),
        ('PHASE 5', 'Déploiement et Production', '3-4 jours'),
        ('',       'Récapitulatif global', ''),
    ]
    for i, (phase, title, dur) in enumerate(toc, 1):
        num_txt = f'{i}.' if phase else f'{i}.'
        dur_txt = f' <font color="#6b7280">({dur})</font>' if dur else ''
        phase_txt = f'<font color="#1a56db"><b>{num_txt}</b></font>  ' if phase else f'<b>{i}.</b>  '
        story.append(Paragraph(f'{phase_txt}{title}{dur_txt}', ST['toc_item']))
        story.append(sp(2))

    story.append(PageBreak())

    # ══════════════════════════════════════════════════════════════════════════
    # PHASE 1 — SOLIDIFIER LES FONDATIONS
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        PhaseHeader(1, 'PHASE 1 — SOLIDIFIER LES FONDATIONS',
                    '5-7 jours', 'Sécuriser le code existant avant d\'ajouter quoi que ce soit'),
        sp(14),
    ]

    # ── Étape 1.1 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 1.1', 'Validation côté entité (Assert)', 1), sp(8), bold('Durée : 1 jour'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Les contraintes de validation sont actuellement dans les FormTypes uniquement. Si demain tu crées une API, '
                 'les données arrivent sans passer par le formulaire et aucune validation ne s\'applique. Il faut ajouter '
                 'des contraintes directement sur les entités.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Ouvrir chaque entité dans <font face="Courier" size="9">src/Entity/</font>'),
        num(2, 'Ajouter l\'import en haut du fichier :'),
        code(['use Symfony\\Component\\Validator\\Constraints as Assert;']),
        sp(4),
        num(3, 'Ajouter les contraintes sur chaque propriété :'),
        sp(3),
        bold('User.php :'),
        code([
            '#[Assert\\NotBlank]',
            '#[Assert\\Email]',
            '#[Assert\\Length(max: 180)]',
            'private ?string $email = null;',
            '',
            '#[Assert\\NotBlank]',
            '#[Assert\\Length(min: 2, max: 100)]',
            'private ?string $nom = null;',
            '',
            '#[Assert\\NotBlank]',
            '#[Assert\\Length(min: 2, max: 100)]',
            'private ?string $prenom = null;',
        ]),
        sp(4),
        bold('Demande.php :'),
        code([
            '#[Assert\\NotBlank(message: \'Le titre est obligatoire.\')]',
            '#[Assert\\Length(max: 255)]',
            'private ?string $titre = null;',
            '',
            '#[Assert\\NotBlank(message: \'La description est obligatoire.\')]',
            'private ?string $description = null;',
            '',
            '#[Assert\\NotNull]',
            'private ?Priorite $priorite = null;',
        ]),
        sp(4),
        bold('Site.php :'),
        code([
            '#[Assert\\NotBlank]',
            '#[Assert\\Length(max: 255)]',
            'private ?string $nom = null;',
            '',
            '#[Assert\\Length(max: 10)]',
            'private ?string $codePostal = null;',
        ]),
        sp(4),
        item('<b>Appliquer la même logique pour :</b> Batiment, Equipement, CategorieEquipement, Intervention, Photo, Organisation'),
        sp(3),
        num(4, 'Pour chaque entité : vérifier que les champs NOT NULL en base ont bien '
               '<font face="Courier" size="9">#[Assert\\NotBlank]</font> ou '
               '<font face="Courier" size="9">#[Assert\\NotNull]</font>'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Vérifier que le container compile toujours',
            'php bin/console lint:container',
            '',
            '# Tester via le code : dans un controller temporaire',
            '$demande = new Demande(); // Vide',
            '$errors = $validator->validate($demande);',
            'dump($errors); // Doit lister les violations',
        ]),
        sp(6),
        validation('Aucun formulaire ne peut être soumis avec des champs obligatoires vides. '
                   'Les messages d\'erreur sont en français.'),
        hr(),
    ]

    # ── Étape 1.2 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 1.2', 'Tests automatisés unitaires', 1), sp(8), bold('Durée : 2-3 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Chaque service métier doit avoir des tests qui vérifient son comportement. '
                 'Si tu casses quelque chose plus tard, les tests te préviennent immédiatement.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Installer PHPUnit :'),
        code(['composer require --dev phpunit/phpunit symfony/test-pack']),
        sp(4),
        num(2, 'Créer la structure de tests :'),
        code([
            'tests/',
            '  Unit/',
            '    Service/',
            '      NumberingServiceTest.php',
            '      InterventionServiceTest.php',
            '      FileUploadServiceTest.php',
            '    Enum/',
            '      PrioriteTest.php',
            '      StatutDemandeTest.php',
            '  Functional/',
            '    Controller/',
            '      SecurityControllerTest.php',
            '      DemandeControllerTest.php',
            '      InterventionControllerTest.php',
        ]),
        sp(4),
        num(3, 'Test NumberingService :'),
        code([
            'class NumberingServiceTest extends TestCase',
            '{',
            '    // Test 1 : Le premier numéro de l\'année est DEM-2026-0001',
            '    public function testPremierNumero(): void',
            '',
            '    // Test 2 : Le deuxième numéro incrémente DEM-2026-0002',
            '    public function testIncrementNumero(): void',
            '',
            '    // Test 3 : Le préfixe INT- fonctionne aussi',
            '    public function testPrefixeIntervention(): void',
            '}',
        ]),
        sp(4),
        num(4, 'Test InterventionService :'),
        code([
            'class InterventionServiceTest extends TestCase',
            '{',
            '    // Test 1 : Démarrer une intervention PLANIFIE → EN_COURS',
            '    public function testDemarrerOk(): void',
            '',
            '    // Test 2 : Démarrer une intervention déjà EN_COURS → exception',
            '    public function testDemarrerDejaEnCours(): void',
            '',
            '    // Test 3 : Terminer sans compte rendu → exception',
            '    public function testTerminerSansCompteRendu(): void',
            '',
            '    // Test 4 : Terminer avec CR → TERMINEE + dureeMinutes calculée',
            '    public function testTerminerOk(): void',
            '',
            '    // Test 5 : Terminer la dernière intervention → demande CLOTURE',
            '    public function testCascadeClotureDemande(): void',
            '}',
        ]),
        sp(4),
        num(5, 'Test Enum :'),
        code([
            'class PrioriteTest extends TestCase',
            '{',
            '    public function testTousCasesOntUnLabel(): void',
            '    {',
            '        foreach (Priorite::cases() as $case) {',
            '            $this->assertNotEmpty($case->label());',
            '        }',
            '    }',
            '}',
        ]),
        sp(4),
        num(6, 'Tests fonctionnels (SecurityControllerTest) :'),
        code([
            'class SecurityControllerTest extends WebTestCase',
            '{',
            '    // Test 1 : /demande sans login → redirect /login',
            '    public function testAccesNonConnecte(): void',
            '',
            '    // Test 2 : Login avec mauvais MDP → erreur',
            '    public function testLoginMauvaisMdp(): void',
            '',
            '    // Test 3 : Login OK → redirect /',
            '    public function testLoginOk(): void',
            '}',
        ]),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Lancer tous les tests',
            'php bin/phpunit',
            '',
            '# Lancer un test spécifique',
            'php bin/phpunit tests/Unit/Service/InterventionServiceTest.php',
            '',
            '# Résultat attendu : tous les tests en vert',
            '# OK (15 tests, 30 assertions)',
        ]),
        sp(6),
        validation('Minimum 15 tests. 100% des tests passent en vert. Les 3 services sont couverts.'),
        hr(),
    ]

    # ── Étape 1.3 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 1.3', 'Build CSS avec Vite (remplacer le CDN)', 1), sp(8), bold('Durée : 1 jour'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Le CDN Tailwind charge 3 Mo sur chaque page. Avec un build Vite, seul le CSS utilisé est inclus (50 Ko). '
                 'C\'est 60x plus léger.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Installer Vite + Tailwind :'),
        code([
            'composer require pentatrion/vite-bundle',
            'npm install -D vite tailwindcss @tailwindcss/vite',
        ]),
        sp(4),
        num(2, 'Créer <font face="Courier" size="9">vite.config.js</font> à la racine :'),
        code([
            "import { defineConfig } from 'vite';",
            "import symfonyPlugin from 'vite-plugin-symfony';",
            "import tailwindcss from '@tailwindcss/vite';",
            '',
            'export default defineConfig({',
            '    plugins: [symfonyPlugin(), tailwindcss()],',
            "    build: { rollupOptions: { input: { app: './assets/app.js' } } }",
            '});',
        ]),
        sp(4),
        num(3, "Créer <font face=\"Courier\" size=\"9\">assets/app.js</font> : <font face=\"Courier\" size=\"9\">import './styles/app.css';</font>"),
        num(4, "Créer <font face=\"Courier\" size=\"9\">assets/styles/app.css</font> : <font face=\"Courier\" size=\"9\">@import \"tailwindcss\";</font>"),
        sp(3),
        num(5, 'Dans <font face="Courier" size="9">base.html.twig</font>, remplacer le CDN par :'),
        code([
            "{{ vite_entry_link_tags('app') }}",
            "{{ vite_entry_script_tags('app') }}",
        ]),
        num(6, 'Supprimer la ligne <font face="Courier" size="9">&lt;script src="https://cdn.tailwindcss.com"&gt;</font> de base.html.twig'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Mode dev (hot reload)',
            'npm run dev',
            '',
            '# Build production',
            'npm run build',
            '',
            '# Vérifier que le dossier public/build/ contient les fichiers CSS/JS',
            'ls public/build/',
        ]),
        sp(6),
        validation("L'app a le même rendu visuel. Le CDN Tailwind est supprimé. npm run build produit un fichier CSS < 100 Ko."),
        hr(),
    ]

    # ── Étape 1.4 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 1.4', 'Sécurité : HTTPS + Headers + Rate Limiting', 1), sp(8), bold('Durée : 1 jour'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Protéger l\'app contre les attaques les plus courantes : brute force sur le login, injection XSS, clickjacking.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, '<b>Rate Limiting sur /login :</b> Créer <font face="Courier" size="9">config/packages/rate_limiter.yaml</font>'),
        code([
            'framework:',
            '    rate_limiter:',
            '        login:',
            '            policy: sliding_window',
            '            limit: 5',
            "            interval: '1 minute'",
        ]),
        sp(4),
        bold('Dans <font face="Courier" size="9">SecurityController::login()</font>, ajouter :'),
        code([
            'use Symfony\\Component\\RateLimiter\\RateLimiterFactory;',
            '',
            'public function login(RateLimiterFactory $loginLimiter, Request $request): Response',
            '{',
            '    $limiter = $loginLimiter->create($request->getClientIp());',
            "    if (false === $limiter->consume(1)->isAccepted()) {",
            "        $this->addFlash('danger', 'Trop de tentatives. Réessayez dans 1 minute.');",
            "        return $this->redirectToRoute('app_login');",
            '    }',
            '    // ... reste du code',
            '}',
        ]),
        sp(4),
        num(2, '<b>Headers de sécurité :</b> Installer NelmioSecurityBundle'),
        code(['composer require nelmio/security-bundle']),
        sp(3),
        bold('Configurer <font face="Courier" size="9">config/packages/nelmio_security.yaml</font> :'),
        code([
            'nelmio_security:',
            '    clickjacking:',
            "        paths: { '^/.*': DENY }",
            '    content_type:',
            '        nosniff: true',
            '    xss_protection:',
            '        enabled: true',
            '    csp:',
            '        enabled: true',
            "        default-src: [\"'self'\"]",
            "        script-src: [\"'self'\", \"'unsafe-inline'\"]",
            "        img-src: [\"'self'\", 'data:']",
        ]),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Test rate limiting : tenter 6 logins rapides',
            '# Le 6ème doit afficher "Trop de tentatives"',
            '',
            '# Vérifier les headers dans le navigateur :',
            '# F12 > Network > cliquer sur la requête > Response Headers',
            '# X-Content-Type-Options: nosniff',
            '# X-Frame-Options: DENY',
        ]),
        sp(6),
        validation('6ème tentative de login bloquée. Headers de sécurité présents dans les réponses HTTP.'),
        PageBreak(),
    ]

    # ══════════════════════════════════════════════════════════════════════════
    # PHASE 2 — FONCTIONNALITÉS MÉTIER
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        PhaseHeader(2, 'PHASE 2 — FONCTIONNALITÉS MÉTIER',
                    '7-10 jours', 'Ajouter les fonctionnalités manquantes pour un produit complet'),
        sp(14),
    ]

    # ── Étape 2.1 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 2.1', 'Audit Trail (historique des actions)', 2), sp(8), bold('Durée : 2 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Chaque changement de statut doit être enregistré : qui a fait quoi, quand, depuis quel statut vers quel '
                 'statut. Indispensable pour la traçabilité en GMAO.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Créer l\'entité <font face="Courier" size="9">AuditLog</font> :'),
        code(['php bin/console make:entity AuditLog']),
        sp(3),
        bold('Champs :'),
    ]
    for field, typ, desc in [
        ('action',       'string 50',           '"statut_change", "creation", "modification", "suppression"'),
        ('entityType',   'string 50',           '"Demande", "Intervention"'),
        ('entityId',     'integer',             ''),
        ('oldValue',     'string 255, nullable','ancien statut'),
        ('newValue',     'string 255, nullable','nouveau statut'),
        ('details',      'text, nullable',      'infos complémentaires'),
        ('user',         'ManyToOne vers User', ''),
        ('organisation', 'ManyToOne vers Organisation', ''),
        ('createdAt',    'datetime_immutable',  ''),
    ]:
        desc_str = f' — {desc}' if desc else ''
        story.append(item(f'<font face="Courier" size="9">{field}</font> ({typ}){desc_str}'))

    story += [
        sp(4),
        num(2, 'Créer le service <font face="Courier" size="9">AuditService</font> :'),
        code([
            'class AuditService',
            '{',
            '    public function log(',
            '        string $action,',
            '        string $entityType,',
            '        int $entityId,',
            '        User $user,',
            '        ?string $oldValue = null,',
            '        ?string $newValue = null,',
            '        ?string $details = null',
            '    ): void',
            '}',
        ]),
        sp(4),
        num(3, 'Appeler <font face="Courier" size="9">AuditService</font> dans <font face="Courier" size="9">InterventionService</font> :'),
        item('Après demarrerIntervention() : log "statut_change" PLANIFIE → EN_COURS'),
        item('Après terminerIntervention() : log "statut_change" EN_COURS → TERMINEE'),
        item('Après valider : log "statut_change" TERMINEE → VALIDEE'),
        sp(4),
        num(4, 'Appeler <font face="Courier" size="9">AuditService</font> dans <font face="Courier" size="9">DemandeController</font> :'),
        item('Après qualifier() : log "statut_change" A_QUALIFIER → QUALIFIE'),
        item('Après rejeter() : log "statut_change" A_QUALIFIER → REJETEE'),
        sp(4),
        num(5, 'Créer une page d\'historique :'),
        item('Route <font face="Courier" size="9">/audit</font> (ADMIN uniquement)'),
        item('Tableau avec : date, utilisateur, action, entité, ancien → nouveau statut'),
        item('Filtres : par entité, par utilisateur, par date'),
        sp(4),
        num(6, 'Migration :'),
        code([
            'php bin/console make:migration',
            'php bin/console doctrine:migrations:migrate',
        ]),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Démarrer une intervention puis vérifier en base :',
            'SELECT * FROM audit_log ORDER BY created_at DESC LIMIT 5;',
            '# Doit montrer : action=statut_change, old_value=PLANIFIE, new_value=EN_COURS',
            '',
            "# Ouvrir /audit en tant qu'admin → tableau rempli",
        ]),
        sp(6),
        validation('Chaque changement de statut génère une ligne dans audit_log. La page /audit affiche l\'historique complet.'),
        hr(),
    ]

    # ── Étape 2.2 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 2.2', 'Notifications email', 2), sp(8), bold('Durée : 2 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Les utilisateurs concernés doivent être prévenus par email aux moments clés du workflow.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Identifier les notifications :'),
        sp(4),
        make_table([
            [Paragraph('<b>Événement</b>', ST['tbl_hdr']),
             Paragraph('<b>Destinataire</b>', ST['tbl_hdr']),
             Paragraph('<b>Sujet</b>', ST['tbl_hdr'])],
            ['Intervention assignée',  'Technicien',    '"Nouvelle intervention assignée"'],
            ['Intervention démarrée',  'Planificateur', '"Intervention démarrée par [tech]"'],
            ['Intervention terminée',  'Planificateur', '"Intervention terminée, en attente de validation"'],
            ['Demande clôturée',        'Demandeur',     '"Votre demande a été traitée"'],
            ['Demande rejetée',         'Demandeur',     '"Votre demande a été rejetée"'],
        ], [DOC_W*0.30, DOC_W*0.20, DOC_W*0.50]),
        sp(6),
        num(2, 'Créer le service <font face="Courier" size="9">NotificationService</font> :'),
        code([
            'class NotificationService',
            '{',
            '    public function __construct(',
            '        private MailerInterface $mailer,',
            '        private Environment $twig',
            '    ) {}',
            '',
            '    public function notifyInterventionAssignee(Intervention $i): void',
            '    public function notifyInterventionDemarree(Intervention $i): void',
            '    public function notifyInterventionTerminee(Intervention $i): void',
            '    public function notifyDemandeCloturee(Demande $d): void',
            '    public function notifyDemandeRejetee(Demande $d): void',
            '}',
        ]),
        sp(4),
        num(3, 'Créer les templates email :'),
        code([
            'templates/email/',
            '  intervention_assignee.html.twig',
            '  intervention_demarree.html.twig',
            '  intervention_terminee.html.twig',
            '  demande_cloturee.html.twig',
            '  demande_rejetee.html.twig',
        ]),
        sp(4),
        num(4, 'Appeler <font face="Courier" size="9">NotificationService</font> dans InterventionService et DemandeController'),
        num(5, 'Configurer un vrai SMTP pour la production (voir DEPLOIEMENT.md)'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# En dev : Symfony intercepte les emails dans le profiler',
            '# Ouvrir la barre de debug → icône enveloppe → vérifier le contenu',
            '',
            '# Ou installer Mailpit (docker) :',
            'docker run -d -p 8025:8025 -p 1025:1025 axllent/mailpit',
            '# Configurer MAILER_DSN=smtp://localhost:1025',
            '# Ouvrir http://localhost:8025 pour voir les emails',
        ]),
        sp(6),
        validation('Chaque action workflow envoie un email au bon destinataire. Les emails sont visibles dans le profiler Symfony.'),
        hr(),
    ]

    # ── Étape 2.3 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 2.3', 'Numérotation transactionnelle', 2), sp(8), bold('Durée : 1 jour'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Empêcher les collisions de numéros en cas d\'accès concurrent '
                 '(2 personnes créent une demande en même temps).'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Créer l\'entité <font face="Courier" size="9">Counter</font> :'),
        code([
            '#[ORM\\Entity]',
            "#[ORM\\UniqueConstraint(fields: ['prefix', 'year'])]",
            'class Counter',
            '{',
            '    #[ORM\\Id] #[ORM\\GeneratedValue] #[ORM\\Column]',
            '    private ?int $id = null;',
            '',
            '    #[ORM\\Column(length: 10)]',
            '    private string $prefix;',
            '',
            '    #[ORM\\Column]',
            '    private int $year;',
            '',
            '    #[ORM\\Column]',
            '    private int $lastValue = 0;',
            '}',
        ]),
        sp(4),
        num(2, 'Modifier <font face="Courier" size="9">NumberingService</font> avec verrou transactionnel (SELECT ... FOR UPDATE) :'),
        code([
            'public function generateNumero(string $prefix): string',
            '{',
            "    $year = (int) date('Y');",
            '    $conn = $this->entityManager->getConnection();',
            '    $conn->beginTransaction();',
            '    try {',
            "        $sql = 'SELECT last_value FROM counter WHERE prefix = ? AND year = ? FOR UPDATE';",
            '        $result = $conn->fetchOne($sql, [$prefix, $year]);',
            '        if ($result === false) {',
            "            $conn->insert('counter', ['prefix'=>$prefix,'year'=>$year,'last_value'=>1]);",
            '            $next = 1;',
            '        } else {',
            '            $next = $result + 1;',
            "            $conn->update('counter', ['last_value'=>$next], ['prefix'=>$prefix,'year'=>$year]);",
            '        }',
            '        $conn->commit();',
            '    } catch (\\Throwable $e) { $conn->rollBack(); throw $e; }',
            "    return sprintf('%s-%d-%04d', $prefix, $year, $next);",
            '}',
        ]),
        sp(4),
        num(3, 'Migration + insertion des compteurs initiaux'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Ouvrir 2 navigateurs en parallèle',
            '# Créer une demande dans chacun en même temps',
            '# Vérifier que les numéros sont différents (pas de doublon)',
            '',
            '# En base :',
            'SELECT * FROM counter;',
            '# Doit montrer prefix=DEM, year=2026, last_value=42',
        ]),
        sp(6),
        validation('Aucun doublon de numéro même en accès concurrent. Table counter correctement incrémentée.'),
        hr(),
    ]

    # ── Étape 2.4 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 2.4', 'Export PDF et Excel', 2), sp(8), bold('Durée : 2 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Les planificateurs veulent exporter les rapports et les listes pour les imprimer ou les envoyer par email.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Installer les dépendances :'),
        code(['composer require dompdf/dompdf phpoffice/phpspreadsheet']),
        sp(4),
        num(2, 'Créer <font face="Courier" size="9">ExportService</font> :'),
        code([
            'class ExportService',
            '{',
            '    public function exportDemandesPdf(array $demandes): string',
            '    public function exportDemandesExcel(array $demandes): string',
            '    public function exportReportingPdf(array $kpiData): string',
            '}',
        ]),
        sp(4),
        num(3, 'Ajouter les routes dans les controllers :'),
        code([
            "#[Route('/demande/export/pdf',      name: 'app_demande_export_pdf')]",
            "#[Route('/demande/export/excel',    name: 'app_demande_export_excel')]",
            "#[Route('/reporting/export/pdf',    name: 'app_reporting_export_pdf')]",
        ]),
        sp(4),
        num(4, 'Créer les templates PDF (HTML simple, sans Tailwind) :'),
        code([
            'templates/export/',
            '  demandes_pdf.html.twig',
            '  reporting_pdf.html.twig',
        ]),
        sp(4),
        num(5, 'Ajouter les boutons dans les templates :'),
        item('Sur la page Demandes : boutons "Export PDF" et "Export Excel"'),
        item('Sur la page Reporting : bouton "Export PDF"'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Cliquer sur "Export PDF" depuis la page Demandes',
            '# Un fichier PDF se télécharge avec le tableau des demandes',
            '',
            '# Cliquer sur "Export Excel"',
            '# Un fichier .xlsx s\'ouvre dans Excel/LibreOffice avec les bonnes colonnes',
        ]),
        sp(6),
        validation('Les PDF et Excel contiennent les bonnes données. Les filtres actifs sont pris en compte dans l\'export.'),
        hr(),
    ]

    # ── Étape 2.5 ─────────────────────────────────────────────────────────
    story += [
        KeepTogether([StepHeader('Étape 2.5', 'SLA et alertes retard', 2), sp(8), bold('Durée : 2 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Définir un délai maximum par priorité. Si une demande dépasse ce délai, une alerte est visible '
                 'sur le dashboard et un email est envoyé.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Définir les SLA :'),
        sp(4),
        make_table([
            [Paragraph('<b>Priorité</b>', ST['tbl_hdr']),
             Paragraph('<b>Délai max</b>', ST['tbl_hdr'])],
            ['P1 — Urgente', '4 heures'],
            ['P2 — Haute',   '24 heures'],
            ['P3 — Normale', '72 heures'],
            ['P4 — Basse',   '168 heures (1 semaine)'],
        ], [DOC_W*0.5, DOC_W*0.5]),
        sp(6),
        num(2, 'Ajouter une méthode dans <font face="Courier" size="9">DemandeRepository</font> :'),
        code(['public function findDemandesEnRetard(Organisation $org): array']),
        sp(4),
        num(3, 'Créer une commande Symfony (CRON) :'),
        code(['php bin/console make:command app:check-sla']),
        sp(3),
        bold('La commande :'),
        item('Cherche toutes les demandes en retard'),
        item('Envoie un email récapitulatif au planificateur'),
        item('Exécutée toutes les heures via CRON'),
        sp(4),
        num(4, 'Ajouter un indicateur visuel sur le Dashboard :'),
        item('Badge rouge "SLA dépassé" sur les demandes en retard'),
        item('Compteur "Demandes en retard SLA" sur le dashboard'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Créer une demande P1 datant de plus de 4h (modifier createdAt en base)',
            '# Lancer la commande :',
            'php bin/console app:check-sla',
            '# Vérifier : email envoyé + badge visible sur le dashboard',
        ]),
        sp(6),
        validation('Les demandes en retard SLA sont identifiées. L\'email contient la liste. Le dashboard les met en évidence.'),
        PageBreak(),
    ]

    # ══════════════════════════════════════════════════════════════════════════
    # PHASE 3 — API ET OUVERTURE
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        PhaseHeader(3, 'PHASE 3 — API ET OUVERTURE',
                    '5-7 jours', 'Préparer l\'application pour une app mobile ou des intégrations'),
        sp(14),
    ]

    story += [
        KeepTogether([StepHeader('Étape 3.1', 'API REST avec API Platform', 3), sp(8), bold('Durée : 3-4 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Exposer les données de la GMAO via une API REST pour qu\'une app mobile ou un autre système '
                 'puisse lire et écrire des données.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Installer API Platform :'),
        code(['composer require api']),
        sp(4),
        num(2, 'Ajouter <font face="Courier" size="9">#[ApiResource]</font> sur les entités principales :'),
        code([
            'use ApiPlatform\\Metadata\\ApiResource;',
            '',
            '#[ApiResource(',
            "    normalizationContext:   ['groups' => ['demande:read']],",
            "    denormalizationContext: ['groups' => ['demande:write']],",
            ')]',
            'class Demande { ... }',
        ]),
        sp(4),
        num(3, 'Configurer les groupes de sérialisation'),
        num(4, 'Sécuriser l\'API avec JWT :'),
        code([
            'composer require lexik/jwt-authentication-bundle',
            'php bin/console lexik:jwt:generate-keypair',
        ]),
        num(5, 'Filtrer par organisation (extension API Platform multi-tenant)'),
        num(6, '<b>Documentation auto :</b> API Platform génère <font face="Courier" size="9">/api/docs</font> (Swagger)'),
        sp(6),
        bold('Comment vérifier que c\'est OK :'),
        code([
            '# Accéder à /api/docs → documentation Swagger',
            'curl -X GET http://localhost:8000/api/demandes \\',
            '     -H "Authorization: Bearer TOKEN"',
            "# Réponse JSON avec les demandes de l'organisation du token",
        ]),
        sp(6),
        validation('/api/docs accessible. CRUD fonctionnel. Filtrage multi-tenant actif. JWT fonctionnel.'),
        hr(),
        KeepTogether([StepHeader('Étape 3.2', 'Recherche avancée (fulltext MySQL)', 3), sp(8), bold('Durée : 1-2 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Avec des milliers de demandes, la recherche LIKE devient lente. Une recherche fulltext est nécessaire.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Ajouter un index FULLTEXT MySQL :'),
        code(['ALTER TABLE demande ADD FULLTEXT INDEX FT_DEMANDE_SEARCH (titre, description);']),
        sp(4),
        num(2, 'Modifier le repository :'),
        code(['public function searchFulltext(Organisation $org, string $query): array']),
        sp(6),
        validation('Recherche pertinente en moins de 200ms sur 1000+ demandes.'),
        PageBreak(),
    ]

    # ══════════════════════════════════════════════════════════════════════════
    # PHASE 4 — INTERFACE ET EXPÉRIENCE
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        PhaseHeader(4, 'PHASE 4 — INTERFACE ET EXPÉRIENCE',
                    '5-7 jours', 'Améliorer l\'expérience utilisateur'),
        sp(14),
    ]

    story += [
        KeepTogether([StepHeader('Étape 4.1', 'Calendrier des interventions', 4), sp(8), bold('Durée : 2-3 jours'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Les planificateurs veulent voir les interventions sur un calendrier mensuel/hebdomadaire.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Installer FullCalendar :'),
        code(['npm install @fullcalendar/core @fullcalendar/daygrid @fullcalendar/timegrid']),
        num(2, 'Créer un endpoint JSON retournant les interventions'),
        num(3, 'Créer la page calendrier avec FullCalendar'),
        num(4, 'Ajouter le lien dans la sidebar'),
        sp(6),
        validation('Calendrier affiche les interventions planifiées. Cliquer sur un événement ouvre le détail.'),
        hr(),
        KeepTogether([StepHeader('Étape 4.2', 'QR Code sur les équipements', 4), sp(8), bold('Durée : 1 jour'), sp(4)]),
        info_box('Ce qui est attendu :',
                 'Un technicien scanne le QR code collé sur un équipement et ouvre directement sa fiche '
                 'pour créer une demande ou voir son historique.'),
        sp(6),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Installer la bibliothèque :'),
        code(['composer require endroid/qr-code']),
        num(2, 'Créer la route <font face="Courier" size="9">/equipement/{id}/qrcode</font> qui génère le QR'),
        num(3, 'Afficher et permettre l\'impression sur la fiche équipement'),
        sp(6),
        validation('Scanner le QR code ouvre la bonne page équipement. L\'image est imprimable.'),
        hr(),
        KeepTogether([StepHeader('Étape 4.3', 'Mode sombre', 4), sp(8), bold('Durée : 1-2 jours'), sp(4)]),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Utiliser les classes Tailwind <font face="Courier" size="9">dark:</font> sur les composants'),
        num(2, 'Bouton toggle dans le header'),
        num(3, 'Persister le choix dans localStorage'),
        sp(6),
        validation('Toggle fonctionne. Couleurs cohérentes. Choix persiste après rechargement.'),
        PageBreak(),
    ]

    # ══════════════════════════════════════════════════════════════════════════
    # PHASE 5 — DÉPLOIEMENT ET PRODUCTION
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        PhaseHeader(5, 'PHASE 5 — DÉPLOIEMENT ET PRODUCTION',
                    '3-4 jours', 'Mettre en ligne l\'application'),
        sp(14),
        KeepTogether([StepHeader('Étape 5.1', 'Déploiement initial', 5), sp(8), bold('Durée : 1-2 jours'), sp(4)]),
        p('Suivre le guide <font face="Courier" size="9">docs/DEPLOIEMENT.md</font> pour déployer sur '
          'Railway.app (MVP) ou Cloudways (production).'),
        sp(6),
        validation('Application accessible en ligne. HTTPS actif. Login et upload fonctionnels.'),
        hr(),
        KeepTogether([StepHeader('Étape 5.2', 'Monitoring et logs', 5), sp(8), bold('Durée : 1 jour'), sp(4)]),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Configurer Monolog pour la production :'),
        code([
            'monolog:',
            '    handlers:',
            '        main:',
            '            type: rotating_file',
            "            path: '%kernel.logs_dir%/%kernel.environment%.log'",
            '            level: warning',
            '            max_files: 14',
        ]),
        sp(4),
        num(2, 'Sentry (gratuit pour petits projets) :'),
        code(['composer require sentry/sentry-symfony']),
        sp(6),
        validation('Erreurs visibles dans Sentry. Logs archivés sur 14 jours.'),
        hr(),
        KeepTogether([StepHeader('Étape 5.3', 'Backup automatique', 5), sp(8), bold('Durée : 0.5 jour'), sp(4)]),
        bold('Ce que tu dois faire :'), sp(3),
        num(1, 'Script de backup MySQL (cron quotidien à 2h du matin)'),
        num(2, 'Conservation des 30 derniers backups'),
        sp(6),
        validation('Backup créé chaque nuit. 30 derniers conservés.'),
        PageBreak(),
    ]

    # ══════════════════════════════════════════════════════════════════════════
    # RÉCAPITULATIF GLOBAL
    # ══════════════════════════════════════════════════════════════════════════
    story += [
        p('Récapitulatif global', 'h1'),
        hr(),
        sp(6),
        make_table([
            [Paragraph('<b>Phase</b>', ST['tbl_hdr']),
             Paragraph('<b>Durée</b>', ST['tbl_hdr']),
             Paragraph('<b>Étapes clés</b>', ST['tbl_hdr'])],
            [Paragraph('<b>Phase 1 — Fondations</b>', ST['tbl_cell']),    '5-7 jours',  'Assert, Tests, Vite, Sécurité'],
            [Paragraph('<b>Phase 2 — Métier</b>', ST['tbl_cell']),        '7-10 jours', 'Audit trail, Notifications, Numérotation, Export, SLA'],
            [Paragraph('<b>Phase 3 — API</b>', ST['tbl_cell']),           '5-7 jours',  'API REST, JWT, Recherche avancée'],
            [Paragraph('<b>Phase 4 — Interface</b>', ST['tbl_cell']),     '5-7 jours',  'Calendrier, QR Code, Dark mode'],
            [Paragraph('<b>Phase 5 — Production</b>', ST['tbl_cell']),    '3-4 jours',  'Deploy, Monitoring, Backup'],
            [Paragraph('<b>TOTAL</b>', ST['tbl_cell']),
             Paragraph('<b>25-35 jours</b>', ST['tbl_cell']), ''],
        ], [DOC_W*0.30, DOC_W*0.18, DOC_W*0.52]),
        sp(20),
        p('Ordre recommandé', 'h1'),
        hr(),
        sp(6),
        code([
            'Semaine 1-2  :  Phase 1 (fondations)       — Assert, Tests, Vite, Sécurité',
            'Semaine 3-4  :  Phase 2 (métier)            — Audit, Notifs, Export, SLA',
            'Semaine 5    :  Phase 5 (déployer en ligne) — v1 en production !',
            'Semaine 6-7  :  Phase 3 (API)               — API REST + JWT',
            'Semaine 8    :  Phase 4 (interface)          — Calendrier, QR, Dark mode',
        ]),
        sp(12),
        ColorBox([
            Paragraph('<b>Conseil :</b> Déployer en semaine 5 (avant Phase 3 et 4) permet d\'avoir une version en ligne '
                      'rapidement et de recueillir des retours utilisateurs pendant que tu continues à développer.',
                      ST['body']),
        ], bg=C_VALID_BG, border=C_VALID_BDR),
    ]

    return story


# ─── MAIN ───────────────────────────────────────────────────────────────────
OUTPUT = '/Users/diakite/GMAO/GMAO/docs/PLAN-AMELIORATIONS.pdf'

doc = SimpleDocTemplate(
    OUTPUT,
    pagesize=A4,
    leftMargin=MARGIN,
    rightMargin=MARGIN,
    topMargin=2.5*cm,
    bottomMargin=2.5*cm,
)

doc.build(build(), onFirstPage=draw_header_footer, onLaterPages=draw_header_footer)
print(f'✓ PDF généré : {OUTPUT}')
