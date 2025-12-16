<?php
/**
 * Système de Breadcrumbs (Fil d'Ariane) Amélioré
 * Usage: include breadcrumbs.php puis appeler renderBreadcrumbs($items)
 * 
 * Chaque item: ['label' => 'Texte', 'url' => '/chemin', 'icon' => '🏠']
 */

class Breadcrumbs {
    private static $items = [];
    private static $homeUrl = '../index.php';
    private static $homeLabel = 'Accueil';
    private static $homeIcon = '🏠';
    
    /**
     * Ajouter un élément au fil d'Ariane
     */
    public static function add($label, $url = null, $icon = null) {
        self::$items[] = [
            'label' => $label,
            'url' => $url,
            'icon' => $icon
        ];
        return new self;
    }
    
    /**
     * Définir la page d'accueil
     */
    public static function setHome($url, $label = 'Accueil', $icon = '🏠') {
        self::$homeUrl = $url;
        self::$homeLabel = $label;
        self::$homeIcon = $icon;
        return new self;
    }
    
    /**
     * Réinitialiser les items
     */
    public static function clear() {
        self::$items = [];
        return new self;
    }
    
    /**
     * Obtenir les items pour le schéma JSON-LD
     */
    public static function getSchemaItems() {
        $all = array_merge([
            ['label' => self::$homeLabel, 'url' => self::$homeUrl, 'icon' => self::$homeIcon]
        ], self::$items);
        
        return array_map(function($item) {
            return ['name' => $item['label'], 'url' => $item['url'] ?? ''];
        }, $all);
    }
    
    /**
     * Rendre le fil d'Ariane
     */
    public static function render($style = 'default') {
        $allItems = array_merge([
            ['label' => self::$homeLabel, 'url' => self::$homeUrl, 'icon' => self::$homeIcon]
        ], self::$items);
        
        if (empty($allItems)) return '';
        
        $html = '<nav class="breadcrumbs breadcrumbs-' . $style . '" aria-label="Fil d\'Ariane">';
        $html .= '<ol class="breadcrumbs-list" itemscope itemtype="https://schema.org/BreadcrumbList">';
        
        foreach ($allItems as $index => $item) {
            $isLast = ($index === count($allItems) - 1);
            $position = $index + 1;
            
            $html .= '<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">';
            
            if (!empty($item['url']) && !$isLast) {
                $html .= '<a href="' . htmlspecialchars($item['url']) . '" class="breadcrumb-link" itemprop="item">';
                if (!empty($item['icon'])) {
                    $html .= '<span class="breadcrumb-icon">' . $item['icon'] . '</span>';
                }
                $html .= '<span itemprop="name">' . htmlspecialchars($item['label']) . '</span>';
                $html .= '</a>';
            } else {
                $html .= '<span class="breadcrumb-current" itemprop="item">';
                if (!empty($item['icon'])) {
                    $html .= '<span class="breadcrumb-icon">' . $item['icon'] . '</span>';
                }
                $html .= '<span itemprop="name">' . htmlspecialchars($item['label']) . '</span>';
                $html .= '</span>';
            }
            
            $html .= '<meta itemprop="position" content="' . $position . '">';
            $html .= '</li>';
            
            if (!$isLast) {
                $html .= '<li class="breadcrumb-separator" aria-hidden="true">›</li>';
            }
        }
        
        $html .= '</ol></nav>';
        
        return $html;
    }
}

// Fonction legacy pour compatibilité
function renderBreadcrumbs($items) {
    if (empty($items)) return;
    ?>
    <nav class="breadcrumbs" aria-label="Fil d'Ariane">
        <ol class="breadcrumbs-list">
        <?php foreach ($items as $index => $item): ?>
            <li class="breadcrumb-item">
            <?php if ($index > 0): ?>
                </li><li class="breadcrumb-separator" aria-hidden="true">›</li><li class="breadcrumb-item">
            <?php endif; ?>
            
            <?php if (isset($item['url']) && $index < count($items) - 1): ?>
                <a href="<?= htmlspecialchars($item['url']) ?>" class="breadcrumb-link">
                    <?php if (!empty($item['icon'])): ?>
                        <span class="breadcrumb-icon"><?= $item['icon'] ?></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['label']) ?>
                </a>
            <?php else: ?>
                <span class="breadcrumb-current">
                    <?php if (!empty($item['icon'])): ?>
                        <span class="breadcrumb-icon"><?= $item['icon'] ?></span>
                    <?php endif; ?>
                    <?= htmlspecialchars($item['label']) ?>
                </span>
            <?php endif; ?>
            </li>
        <?php endforeach; ?>
        </ol>
    </nav>
    <?php
}

function getBreadcrumbStyles() {
    return <<<CSS
    <style>
        .breadcrumbs {
            margin-bottom: 25px;
        }
        
        .breadcrumbs-list {
            display: flex;
            align-items: center;
            gap: 0;
            padding: 15px 20px;
            background: var(--bg-card, #fff);
            border-radius: 12px;
            list-style: none;
            margin: 0;
            box-shadow: 0 2px 15px rgba(0, 0, 0, 0.05);
            font-size: 0.95em;
            flex-wrap: wrap;
            border: 1px solid var(--border-color, #e2e8f0);
        }
        
        .breadcrumb-item {
            display: flex;
            align-items: center;
        }

        .breadcrumb-link {
            color: var(--accent, #667eea);
            text-decoration: none;
            transition: all 0.3s;
            padding: 6px 12px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .breadcrumb-link:hover {
            background: rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }
        
        .breadcrumb-icon {
            font-size: 1.1em;
        }

        .breadcrumb-separator {
            color: var(--text-secondary, #64748b);
            opacity: 0.5;
            font-weight: bold;
            padding: 0 5px;
            list-style: none;
        }

        .breadcrumb-current {
            color: var(--text-primary, #1e293b);
            font-weight: 600;
            padding: 6px 12px;
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(102, 126, 234, 0.08);
            border-radius: 8px;
        }

        /* Style Pills */
        .breadcrumbs-pills .breadcrumbs-list {
            background: transparent;
            box-shadow: none;
            border: none;
            padding: 0;
            gap: 8px;
        }
        
        .breadcrumbs-pills .breadcrumb-link,
        .breadcrumbs-pills .breadcrumb-current {
            background: var(--bg-card, #fff);
            border: 1px solid var(--border-color, #e2e8f0);
            box-shadow: 0 2px 8px rgba(0,0,0,0.05);
        }
        
        .breadcrumbs-pills .breadcrumb-separator {
            display: none;
        }
        
        .breadcrumbs-pills .breadcrumb-link::after,
        .breadcrumbs-pills .breadcrumb-current::after {
            content: '→';
            margin-left: 8px;
            opacity: 0.5;
        }
        
        .breadcrumbs-pills .breadcrumb-item:last-child .breadcrumb-current::after {
            display: none;
        }

        /* Style Minimal */
        .breadcrumbs-minimal .breadcrumbs-list {
            background: transparent;
            box-shadow: none;
            border: none;
            padding: 10px 0;
        }
        
        .breadcrumbs-minimal .breadcrumb-link,
        .breadcrumbs-minimal .breadcrumb-current {
            padding: 0;
            background: none;
        }
        
        .breadcrumbs-minimal .breadcrumb-link:hover {
            background: none;
            text-decoration: underline;
        }

        /* Dark mode */
        [data-theme="dark"] .breadcrumbs-list {
            background: var(--bg-card, #1e293b);
            border-color: var(--border-color, #334155);
        }
        
        [data-theme="dark"] .breadcrumb-current {
            background: rgba(102, 126, 234, 0.15);
        }

        @media (max-width: 768px) {
            .breadcrumbs-list {
                font-size: 0.85em;
                padding: 12px 15px;
            }
            
            .breadcrumb-link,
            .breadcrumb-current {
                padding: 4px 8px;
            }
            
            .breadcrumb-icon {
                display: none;
            }
        }
    </style>
CSS;
}
?>
