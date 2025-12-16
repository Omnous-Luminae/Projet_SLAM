<?php
/**
 * Classe SEO
 * Gestion centralisée des métadonnées SEO
 */

class SEO {
    private static $defaults = [
        'site_name' => 'House After Party',
        'title_suffix' => ' | House After Party',
        'description' => 'Trouvez le lieu idéal pour vos fêtes et événements. Location de biens pour soirées, mariages et célébrations.',
        'keywords' => 'location, fête, événement, mariage, soirée, celebration, party, house, villa',
        'author' => 'House After Party',
        'image' => '/images/og-default.jpg',
        'type' => 'website',
        'locale' => 'fr_FR',
        'twitter_card' => 'summary_large_image'
    ];
    
    private $title;
    private $description;
    private $keywords;
    private $image;
    private $url;
    private $type;
    private $customMeta = [];
    
    public function __construct() {
        $this->title = '';
        $this->description = self::$defaults['description'];
        $this->keywords = self::$defaults['keywords'];
        $this->type = self::$defaults['type'];
        $this->url = $this->getCurrentUrl();
    }
    
    /**
     * Définir le titre de la page
     */
    public function setTitle($title, $addSuffix = true) {
        $this->title = htmlspecialchars($title);
        if ($addSuffix && !empty(self::$defaults['title_suffix'])) {
            $this->title .= self::$defaults['title_suffix'];
        }
        return $this;
    }
    
    /**
     * Définir la description
     */
    public function setDescription($description) {
        // Limite à 160 caractères pour SEO optimal
        $this->description = htmlspecialchars(substr($description, 0, 160));
        return $this;
    }
    
    /**
     * Définir les mots-clés
     */
    public function setKeywords($keywords) {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->keywords = htmlspecialchars($keywords);
        return $this;
    }
    
    /**
     * Ajouter des mots-clés
     */
    public function addKeywords($keywords) {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        $this->keywords .= ', ' . htmlspecialchars($keywords);
        return $this;
    }
    
    /**
     * Définir l'image Open Graph
     */
    public function setImage($imageUrl) {
        $this->image = $imageUrl;
        return $this;
    }
    
    /**
     * Définir le type de page (website, article, product, etc.)
     */
    public function setType($type) {
        $this->type = $type;
        return $this;
    }
    
    /**
     * Définir l'URL canonique
     */
    public function setUrl($url) {
        $this->url = $url;
        return $this;
    }
    
    /**
     * Ajouter une balise meta personnalisée
     */
    public function addMeta($name, $content, $isProperty = false) {
        $this->customMeta[] = [
            'name' => $name,
            'content' => $content,
            'isProperty' => $isProperty
        ];
        return $this;
    }
    
    /**
     * Obtenir l'URL actuelle
     */
    private function getCurrentUrl() {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
        return $protocol . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/');
    }
    
    /**
     * Générer les balises meta SEO
     */
    public function render() {
        $html = '';
        
        // Titre
        $html .= "<title>{$this->title}</title>\n";
        
        // Meta basiques
        $html .= "<meta name=\"description\" content=\"{$this->description}\">\n";
        $html .= "<meta name=\"keywords\" content=\"{$this->keywords}\">\n";
        $html .= "<meta name=\"author\" content=\"" . self::$defaults['author'] . "\">\n";
        
        // URL canonique
        $html .= "<link rel=\"canonical\" href=\"{$this->url}\">\n";
        
        // Open Graph
        $html .= "<meta property=\"og:title\" content=\"{$this->title}\">\n";
        $html .= "<meta property=\"og:description\" content=\"{$this->description}\">\n";
        $html .= "<meta property=\"og:type\" content=\"{$this->type}\">\n";
        $html .= "<meta property=\"og:url\" content=\"{$this->url}\">\n";
        $html .= "<meta property=\"og:site_name\" content=\"" . self::$defaults['site_name'] . "\">\n";
        $html .= "<meta property=\"og:locale\" content=\"" . self::$defaults['locale'] . "\">\n";
        
        if ($this->image) {
            $html .= "<meta property=\"og:image\" content=\"{$this->image}\">\n";
        }
        
        // Twitter Card
        $html .= "<meta name=\"twitter:card\" content=\"" . self::$defaults['twitter_card'] . "\">\n";
        $html .= "<meta name=\"twitter:title\" content=\"{$this->title}\">\n";
        $html .= "<meta name=\"twitter:description\" content=\"{$this->description}\">\n";
        
        if ($this->image) {
            $html .= "<meta name=\"twitter:image\" content=\"{$this->image}\">\n";
        }
        
        // Meta personnalisées
        foreach ($this->customMeta as $meta) {
            $attr = $meta['isProperty'] ? 'property' : 'name';
            $html .= "<meta {$attr}=\"{$meta['name']}\" content=\"{$meta['content']}\">\n";
        }
        
        // Robots
        $html .= "<meta name=\"robots\" content=\"index, follow\">\n";
        
        return $html;
    }
    
    /**
     * Générer le schema.org JSON-LD pour un bien immobilier
     */
    public static function schemaProperty($bien) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $bien['nom_bien'] ?? 'Bien',
            'description' => $bien['description'] ?? '',
            'category' => 'Vacation Rental',
        ];
        
        if (!empty($bien['photo'])) {
            $schema['image'] = $bien['photo'];
        }
        
        if (!empty($bien['adresse']) || !empty($bien['nom_commune'])) {
            $schema['address'] = [
                '@type' => 'PostalAddress',
                'streetAddress' => $bien['adresse'] ?? '',
                'addressLocality' => $bien['nom_commune'] ?? '',
                'postalCode' => $bien['code_postal'] ?? '',
                'addressCountry' => 'FR'
            ];
        }
        
        if (!empty($bien['note_moyenne'])) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => $bien['note_moyenne'],
                'bestRating' => 5,
                'worstRating' => 1,
                'reviewCount' => $bien['nb_avis'] ?? 1
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Générer le schema.org JSON-LD pour une organisation
     */
    public static function schemaOrganization() {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => 'House After Party',
            'description' => self::$defaults['description'],
            'url' => 'http://localhost/Projet_HAP(House_After_Party)/',
            'logo' => 'http://localhost/Projet_HAP(House_After_Party)/images/logo.png',
            'contactPoint' => [
                '@type' => 'ContactPoint',
                'contactType' => 'customer service',
                'availableLanguage' => ['French']
            ]
        ];
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
    
    /**
     * Générer le schema.org JSON-LD pour les breadcrumbs
     */
    public static function schemaBreadcrumbs($items) {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => []
        ];
        
        foreach ($items as $index => $item) {
            $schema['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $index + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? ''
            ];
        }
        
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . '</script>';
    }
}
