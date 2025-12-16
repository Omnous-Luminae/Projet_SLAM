<?php
/**
 * Classe Pagination
 * Système de pagination réutilisable pour toutes les listes
 */

class Pagination {
    private $totalItems;
    private $itemsPerPage;
    private $currentPage;
    private $totalPages;
    private $urlPattern;
    
    /**
     * Constructeur
     * @param int $totalItems Nombre total d'éléments
     * @param int $itemsPerPage Nombre d'éléments par page (défaut: 10)
     * @param int $currentPage Page actuelle (défaut: 1)
     * @param string $urlPattern Pattern d'URL avec {page} comme placeholder
     */
    public function __construct($totalItems, $itemsPerPage = 10, $currentPage = 1, $urlPattern = '?page={page}') {
        $this->totalItems = max(0, (int)$totalItems);
        $this->itemsPerPage = max(1, (int)$itemsPerPage);
        $this->totalPages = max(1, ceil($this->totalItems / $this->itemsPerPage));
        $this->currentPage = max(1, min((int)$currentPage, $this->totalPages));
        $this->urlPattern = $urlPattern;
    }
    
    /**
     * Obtenir l'offset pour la requête SQL
     */
    public function getOffset() {
        return ($this->currentPage - 1) * $this->itemsPerPage;
    }
    
    /**
     * Obtenir la limite pour la requête SQL
     */
    public function getLimit() {
        return $this->itemsPerPage;
    }
    
    /**
     * Obtenir la page actuelle
     */
    public function getCurrentPage() {
        return $this->currentPage;
    }
    
    /**
     * Obtenir le nombre total de pages
     */
    public function getTotalPages() {
        return $this->totalPages;
    }
    
    /**
     * Obtenir le nombre total d'items
     */
    public function getTotalItems() {
        return $this->totalItems;
    }
    
    /**
     * Vérifier s'il y a une page précédente
     */
    public function hasPrevPage() {
        return $this->currentPage > 1;
    }
    
    /**
     * Vérifier s'il y a une page suivante
     */
    public function hasNextPage() {
        return $this->currentPage < $this->totalPages;
    }
    
    /**
     * Générer l'URL pour une page donnée
     */
    public function getUrl($page) {
        return str_replace('{page}', $page, $this->urlPattern);
    }
    
    /**
     * Obtenir la plage de pages à afficher
     */
    public function getPageRange($maxVisible = 5) {
        $half = floor($maxVisible / 2);
        $start = max(1, $this->currentPage - $half);
        $end = min($this->totalPages, $start + $maxVisible - 1);
        
        // Ajuster le début si on est proche de la fin
        if ($end - $start + 1 < $maxVisible) {
            $start = max(1, $end - $maxVisible + 1);
        }
        
        return range($start, $end);
    }
    
    /**
     * Générer le HTML de la pagination
     */
    public function render($style = 'default') {
        if ($this->totalPages <= 1) {
            return '';
        }
        
        $html = '<nav class="pagination-wrapper" aria-label="Pagination">';
        $html .= '<ul class="pagination pagination-' . $style . '">';
        
        // Info sur les résultats
        $start = $this->getOffset() + 1;
        $end = min($this->getOffset() + $this->itemsPerPage, $this->totalItems);
        $html .= '<li class="pagination-info">';
        $html .= "<span>{$start}-{$end} sur {$this->totalItems}</span>";
        $html .= '</li>';
        
        // Bouton précédent
        if ($this->hasPrevPage()) {
            $html .= '<li><a href="' . $this->getUrl($this->currentPage - 1) . '" class="pagination-btn prev">← Précédent</a></li>';
        } else {
            $html .= '<li><span class="pagination-btn prev disabled">← Précédent</span></li>';
        }
        
        // Première page
        if ($this->getPageRange()[0] > 1) {
            $html .= '<li><a href="' . $this->getUrl(1) . '" class="pagination-number">1</a></li>';
            if ($this->getPageRange()[0] > 2) {
                $html .= '<li><span class="pagination-ellipsis">...</span></li>';
            }
        }
        
        // Pages du milieu
        foreach ($this->getPageRange() as $page) {
            if ($page == $this->currentPage) {
                $html .= '<li><span class="pagination-number active">' . $page . '</span></li>';
            } else {
                $html .= '<li><a href="' . $this->getUrl($page) . '" class="pagination-number">' . $page . '</a></li>';
            }
        }
        
        // Dernière page
        $lastInRange = end($this->getPageRange());
        if ($lastInRange < $this->totalPages) {
            if ($lastInRange < $this->totalPages - 1) {
                $html .= '<li><span class="pagination-ellipsis">...</span></li>';
            }
            $html .= '<li><a href="' . $this->getUrl($this->totalPages) . '" class="pagination-number">' . $this->totalPages . '</a></li>';
        }
        
        // Bouton suivant
        if ($this->hasNextPage()) {
            $html .= '<li><a href="' . $this->getUrl($this->currentPage + 1) . '" class="pagination-btn next">Suivant →</a></li>';
        } else {
            $html .= '<li><span class="pagination-btn next disabled">Suivant →</span></li>';
        }
        
        $html .= '</ul>';
        $html .= '</nav>';
        
        return $html;
    }
    
    /**
     * Générer la clause LIMIT pour SQL
     */
    public function getSqlLimit() {
        return "LIMIT {$this->itemsPerPage} OFFSET {$this->getOffset()}";
    }
    
    /**
     * Méthode statique pour obtenir facilement la page depuis $_GET
     */
    public static function getCurrentPageFromRequest($paramName = 'page') {
        return isset($_GET[$paramName]) ? max(1, (int)$_GET[$paramName]) : 1;
    }
    
    /**
     * Créer le pattern d'URL en préservant les autres paramètres GET
     */
    public static function buildUrlPattern($pageParam = 'page') {
        $params = $_GET;
        $params[$pageParam] = '{page}';
        return '?' . http_build_query($params);
    }
}
