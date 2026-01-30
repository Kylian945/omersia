<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Omersia\Catalog\Models\Category;
use Omersia\Catalog\Models\CategoryTranslation;
use Omersia\Catalog\Models\Product;
use Omersia\Catalog\Models\ProductImage;
use Omersia\Catalog\Models\ProductTranslation;
use Omersia\Core\Models\Shop;

class DemoProductsSeeder extends Seeder
{
    private ?int $shopId = null;

    private string $seedImagesPath = 'database/seeders/images/products';

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->shopId = Shop::first()?->id;

        if (! $this->shopId) {
            $this->command->error('No shop found. Please create a shop first.');

            return;
        }

        $this->command->info('🌱 Seeding demo categories and products...');

        // Créer les catégories
        $categories = $this->createCategories();
        $this->command->info('✅ Created '.count($categories).' categories');

        // Créer les produits
        $this->createProducts($categories);
        $this->command->info('✅ Created demo products');

        $this->command->info('🎉 Demo data seeded successfully!');
    }

    /**
     * Create categories with translations (structure lifestyle/sport avec 3 niveaux)
     */
    private function createCategories(): array
    {
        $categoriesData = [
            [
                'name' => 'Accueil',
                'description' => 'Découvrez notre sélection de produits lifestyle et sport',
                'children' => [],
            ],
            [
                'name' => 'Vêtements',
                'description' => 'Collection complète de vêtements pour tous',
                'children' => [
                    ['name' => 'Homme', 'description' => 'Vêtements pour homme'],
                    ['name' => 'Femme', 'description' => 'Vêtements pour femme'],
                    ['name' => 'Accessoires', 'description' => 'Accessoires vestimentaires'],
                ],
            ],
            [
                'name' => 'Accessoires',
                'description' => 'Accessoires lifestyle pour le quotidien',
                'children' => [
                    [
                        'name' => 'Maison',
                        'description' => 'Accessoires pour la maison',
                        'children' => [
                            ['name' => 'Bureau', 'description' => 'Équipement de bureau et high-tech'],
                        ],
                    ],
                    ['name' => 'Bagagerie', 'description' => 'Sacs et bagages pour tous vos déplacements'],
                ],
            ],
            [
                'name' => 'Sport',
                'description' => 'Équipements sportifs et accessoires fitness',
                'children' => [],
            ],
        ];

        $categories = [];
        $position = 0;

        foreach ($categoriesData as $categoryData) {
            // Niveau 1
            $level1 = Category::create([
                'shop_id' => $this->shopId,
                'parent_id' => null,
                'is_active' => true,
                'position' => $position++,
            ]);

            CategoryTranslation::create([
                'category_id' => $level1->id,
                'locale' => 'fr',
                'name' => $categoryData['name'],
                'slug' => Str::slug($categoryData['name']),
                'description' => $categoryData['description'],
                'meta_title' => $categoryData['name'].' - Omersia',
                'meta_description' => $categoryData['description'],
            ]);

            $categories[$categoryData['name']] = $level1;

            // Niveau 2
            if (isset($categoryData['children']) && count($categoryData['children']) > 0) {
                $level2Position = 0;
                foreach ($categoryData['children'] as $level2Data) {
                    $level2 = Category::create([
                        'shop_id' => $this->shopId,
                        'parent_id' => $level1->id,
                        'is_active' => true,
                        'position' => $level2Position++,
                    ]);

                    CategoryTranslation::create([
                        'category_id' => $level2->id,
                        'locale' => 'fr',
                        'name' => $level2Data['name'],
                        'slug' => Str::slug($categoryData['name'].'-'.$level2Data['name']),
                        'description' => $level2Data['description'],
                        'meta_title' => $level2Data['name'].' - '.$categoryData['name'].' - Omersia',
                        'meta_description' => $level2Data['description'],
                    ]);

                    $categories[$categoryData['name'].'/'.$level2Data['name']] = $level2;

                    // Niveau 3 (si existe)
                    if (isset($level2Data['children']) && count($level2Data['children']) > 0) {
                        $level3Position = 0;
                        foreach ($level2Data['children'] as $level3Data) {
                            $level3 = Category::create([
                                'shop_id' => $this->shopId,
                                'parent_id' => $level2->id,
                                'is_active' => true,
                                'position' => $level3Position++,
                            ]);

                            CategoryTranslation::create([
                                'category_id' => $level3->id,
                                'locale' => 'fr',
                                'name' => $level3Data['name'],
                                'slug' => Str::slug($categoryData['name'].'-'.$level2Data['name'].'-'.$level3Data['name']),
                                'description' => $level3Data['description'],
                                'meta_title' => $level3Data['name'].' - '.$level2Data['name'].' - Omersia',
                                'meta_description' => $level3Data['description'],
                            ]);

                            $categories[$categoryData['name'].'/'.$level2Data['name'].'/'.$level3Data['name']] = $level3;
                        }
                    }
                }
            }
        }

        return $categories;
    }

    /**
     * Create lifestyle/sport products with images
     */
    private function createProducts(array $categories): void
    {
        $products = [
            // Vêtements / Homme (3 produits)
            [
                'name' => 'Sweat à Capuche Homme',
                'sku' => 'VET-SWEAT-H-001',
                'price' => 69.90,
                'compare_at_price' => null,
                'category' => ['Vêtements/Homme', 'Accueil'],
                'short_description' => 'Sweat à capuche premium en coton bio pour homme',
                'description' => 'Sweat à capuche homme en coton bio 100%, coupe moderne et confortable. Tissu doux et respirant, parfait pour un usage quotidien ou sportif. Disponible en plusieurs coloris tendance avec finitions soignées et poche kangourou.',
                'images' => ['sweat-homme.png', 'sweat-homme-2.png', 'sweat-homme-3.png'],
                'type' => 'variant',
                'variants' => [
                    ['name' => 'S', 'sku' => 'VET-SWEAT-H-001-S', 'stock' => 15],
                    ['name' => 'M', 'sku' => 'VET-SWEAT-H-001-M', 'stock' => 20],
                    ['name' => 'L', 'sku' => 'VET-SWEAT-H-001-L', 'stock' => 15],
                ],
                'option_name' => 'Taille',
            ],
            [
                'name' => 'T-Shirt Homme Coton Bio',
                'sku' => 'VET-TSHIRT-H-001',
                'price' => 29.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Homme',
                'short_description' => 'T-shirt basique en coton bio certifié GOTS',
                'description' => 'T-shirt homme en coton bio certifié GOTS, coupe ajustée et confortable. Matière respirante et douce au toucher, parfait pour toutes les saisons. Col rond renforcé et coutures doubles pour une durabilité optimale.',
                'images' => ['tshirt-homme.png'],
                'type' => 'variant',
                'variants' => [
                    ['name' => 'S', 'sku' => 'VET-TSHIRT-H-001-S', 'stock' => 25],
                    ['name' => 'M', 'sku' => 'VET-TSHIRT-H-001-M', 'stock' => 30],
                    ['name' => 'L', 'sku' => 'VET-TSHIRT-H-001-L', 'stock' => 25],
                ],
                'option_name' => 'Taille',
            ],
            [
                'name' => 'Doudoune Homme Hiver',
                'sku' => 'VET-DOUDOUNE-H-001',
                'price' => 149.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Homme',
                'short_description' => 'Doudoune chaude et légère pour l\'hiver',
                'description' => 'Doudoune homme ultra-légère avec garnissage synthétique haute performance. Résistante au vent et déperlante, avec capuche ajustable et multiples poches zippées. Parfaite pour affronter le froid avec style.',
                'images' => ['doudoune.png'],
                'stock' => 30,
            ],

            // Vêtements / Femme (3 produits)
            [
                'name' => 'Sweat à Capuche Femme',
                'sku' => 'VET-SWEAT-F-001',
                'price' => 59.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Femme',
                'short_description' => 'Sweat à capuche femme confortable et stylé',
                'description' => 'Sweat à capuche femme en molleton doux, coupe féminine ajustée. Matière respirante et confortable, idéale pour le sport ou le quotidien. Capuche réglable et poche kangourou pratique.',
                'images' => ['sweat-femme.png'],
                'type' => 'variant',
                'variants' => [
                    ['name' => 'S', 'sku' => 'VET-SWEAT-F-001-S', 'stock' => 20],
                    ['name' => 'M', 'sku' => 'VET-SWEAT-F-001-M', 'stock' => 20],
                    ['name' => 'L', 'sku' => 'VET-SWEAT-F-001-L', 'stock' => 20],
                ],
                'option_name' => 'Taille',
            ],
            [
                'name' => 'T-Shirt Femme Sport',
                'sku' => 'VET-TSHIRT-F-001',
                'price' => 24.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Femme',
                'short_description' => 'T-shirt technique pour le sport et le fitness',
                'description' => 'T-shirt femme en tissu technique respirant et évacuant l\'humidité. Coupe ergonomique pour une liberté de mouvement optimale. Séchage rapide et propriétés anti-odeurs pour vos entraînements intensifs.',
                'images' => ['tshirt-femme.png'],
                'type' => 'variant',
                'variants' => [
                    ['name' => 'S', 'sku' => 'VET-TSHIRT-F-001-S', 'stock' => 25],
                    ['name' => 'M', 'sku' => 'VET-TSHIRT-F-001-M', 'stock' => 25],
                    ['name' => 'L', 'sku' => 'VET-TSHIRT-F-001-L', 'stock' => 25],
                ],
                'option_name' => 'Taille',
            ],
            [
                'name' => 'Brassière de Sport Femme',
                'sku' => 'VET-BRASSIERE-F-001',
                'price' => 34.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Femme',
                'short_description' => 'Brassière de sport à maintien élevé',
                'description' => 'Brassière de sport avec maintien élevé, idéale pour les activités intensives. Tissu technique respirant, bretelles ajustables et bande sous-poitrine confortable. Design moderne avec détails réfléchissants.',
                'images' => ['brassiere-femme.png'],
                'stock' => 45,
            ],

            // Vêtements / Accessoires (3 produits)
            [
                'name' => 'Casquette Baseball',
                'sku' => 'VET-CASQUETTE-001',
                'price' => 19.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Accessoires',
                'short_description' => 'Casquette baseball ajustable unisexe',
                'description' => 'Casquette baseball en coton avec visière préformée et fermeture ajustable. Protection solaire optimale, design intemporel et bandeau anti-transpiration intégré. Parfaite pour le sport ou le quotidien.',
                'images' => ['casquette.png'],
                'stock' => 100,
            ],
            [
                'name' => 'Lunettes de Soleil Sport',
                'sku' => 'VET-LUNETTES-001',
                'price' => 39.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Accessoires',
                'short_description' => 'Lunettes de soleil polarisées pour le sport',
                'description' => 'Lunettes de soleil sport avec verres polarisés UV400, monture légère et résistante. Branches antidérapantes et protection latérale optimale. Idéales pour le running, cyclisme et sports outdoor.',
                'images' => ['lunettes-soleil.png'],
                'stock' => 55,
            ],
            [
                'name' => 'Chaussettes Sport Pack x3',
                'sku' => 'VET-CHAUSSETTES-001',
                'price' => 14.90,
                'compare_at_price' => null,
                'category' => 'Vêtements/Accessoires',
                'short_description' => 'Pack de 3 paires de chaussettes de sport',
                'description' => 'Pack de 3 paires de chaussettes sport en coton et polyester. Renfort au talon et à la pointe, bande élastique confortable, propriétés anti-odeurs. Parfaites pour toutes vos activités sportives.',
                'images' => ['chaussettes.png'],
                'stock' => 120,
            ],

            // Accessoires / Maison (5 produits)
            [
                'name' => 'Gourde Inox 750ml',
                'sku' => 'ACC-GOURDE-001',
                'price' => 24.90,
                'compare_at_price' => null,
                'category' => ['Accessoires/Maison', 'Accueil'],
                'short_description' => 'Gourde isotherme en acier inoxydable 750ml',
                'description' => 'Gourde isotherme en acier inoxydable avec double paroi isolante. Maintient vos boissons chaudes pendant 12h ou froides pendant 24h. Sans BPA, étanche et facile à nettoyer. Parfaite pour le sport, le bureau ou les voyages.',
                'images' => ['gourde.png'],
                'stock' => 85,
            ],
            [
                'name' => 'Serviette Microfibre',
                'sku' => 'ACC-SERVIETTE-001',
                'price' => 19.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison',
                'short_description' => 'Serviette microfibre ultra-absorbante',
                'description' => 'Serviette en microfibre ultra-absorbante et à séchage rapide. Légère et compacte, elle se range facilement dans son étui de transport. Idéale pour le sport, la piscine, la plage ou les voyages.',
                'images' => ['serviette.png'],
                'stock' => 70,
            ],
            [
                'name' => 'Enceinte Bluetooth Portable',
                'sku' => 'ACC-ENCEINTE-001',
                'price' => 49.90,
                'compare_at_price' => null,
                'category' => ['Accessoires/Maison', 'Accueil'],
                'short_description' => 'Enceinte Bluetooth compacte et puissante',
                'description' => 'Enceinte Bluetooth portable avec son puissant et basses profondes. Autonomie de 10 heures, résistante à l\'eau IPX7, connexion sans fil jusqu\'à 10 mètres. Design compact et robuste pour tous vos déplacements.',
                'images' => ['enceinte.png'],
                'stock' => 40,
            ],
            [
                'name' => 'Set Barbecue 5 Pièces',
                'sku' => 'ACC-BARBECUE-001',
                'price' => 34.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison',
                'short_description' => 'Kit complet d\'ustensiles pour barbecue',
                'description' => 'Set de 5 ustensiles essentiels pour barbecue en acier inoxydable. Comprend spatule, fourchette, pince, brosse de nettoyage et couteau. Manches ergonomiques en bois et étui de rangement inclus.',
                'images' => ['set-barbecue.png'],
                'stock' => 25,
            ],
            [
                'name' => 'Tablier de Cuisine',
                'sku' => 'ACC-TABLIER-001',
                'price' => 16.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison',
                'short_description' => 'Tablier de cuisine en coton résistant',
                'description' => 'Tablier de cuisine en coton épais avec sangles ajustables et large poche frontale. Résistant aux taches et facile à laver. Design intemporel et confortable pour cuisiner avec style.',
                'images' => ['tablier.png'],
                'stock' => 60,
            ],

            // Accessoires / Maison / Bureau (7 produits - NIVEAU 3)
            [
                'name' => 'Carnet A5 Ligné',
                'sku' => 'ACC-CARNET-001',
                'price' => 9.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison/Bureau',
                'short_description' => 'Carnet A5 avec pages lignées et couverture rigide',
                'description' => 'Carnet A5 de qualité avec 192 pages lignées, papier 80g/m² résistant à l\'encre. Couverture rigide élégante, fermeture élastique et marque-page intégré. Parfait pour vos notes, idées et projets.',
                'images' => ['carnet.png'],
                'stock' => 150,
            ],
            [
                'name' => 'Stylo Bille Premium',
                'sku' => 'ACC-STYLO-001',
                'price' => 12.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison/Bureau',
                'short_description' => 'Stylo bille métallique à encre gel',
                'description' => 'Stylo bille premium en métal avec encre gel noire fluide. Corps métallique robuste avec grip ergonomique, clip de poche pratique. Écriture douce et précise pour un confort d\'utilisation optimal.',
                'images' => ['stylo.png'],
                'stock' => 200,
            ],
            [
                'name' => 'Mug Isotherme 350ml',
                'sku' => 'ACC-MUG-001',
                'price' => 19.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison/Bureau',
                'short_description' => 'Mug isotherme en acier inoxydable avec couvercle',
                'description' => 'Mug isotherme 350ml en acier inoxydable avec double paroi. Maintient vos boissons chaudes 6h ou froides 12h. Couvercle étanche anti-fuite, compatible lave-vaisselle. Idéal pour le bureau ou les déplacements.',
                'images' => ['mug.png'],
                'stock' => 90,
            ],
            [
                'name' => 'Souris Sans Fil Ergonomique',
                'sku' => 'ACC-SOURIS-001',
                'price' => 29.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison/Bureau',
                'short_description' => 'Souris sans fil ergonomique rechargeable',
                'description' => 'Souris sans fil ergonomique avec capteur optique précis 1600 DPI. Design vertical pour réduire la fatigue du poignet, batterie rechargeable autonomie 60 jours. Connexion USB 2.4GHz stable et silencieuse.',
                'images' => ['souris.png'],
                'stock' => 65,
            ],
            [
                'name' => 'Clé USB 32Go',
                'sku' => 'ACC-CLE-USB-001',
                'price' => 14.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison/Bureau',
                'short_description' => 'Clé USB 3.0 haute vitesse 32Go',
                'description' => 'Clé USB 3.0 de 32Go avec vitesse de transfert jusqu\'à 100 Mo/s. Design compact en métal robuste, capuchon rotatif protecteur. Compatible PC, Mac, consoles et lecteurs multimédia.',
                'images' => ['cle-usb.png'],
                'stock' => 180,
            ],
            [
                'name' => 'Batterie Externe 10000mAh',
                'sku' => 'ACC-BATTERIE-001',
                'price' => 39.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Maison/Bureau',
                'short_description' => 'Batterie externe 10000mAh charge rapide',
                'description' => 'Batterie externe 10000mAh avec charge rapide 18W et 2 ports USB. Design compact et léger, indicateur LED de niveau de batterie. Protection contre surcharge, surchauffe et court-circuit. Rechargez vos appareils partout.',
                'images' => ['batterie-externe.png'],
                'stock' => 50,
            ],
            [
                'name' => 'Base de Recharge Sans Fil',
                'sku' => 'ACC-RECHARGE-001',
                'price' => 34.90,
                'compare_at_price' => null,
                'category' => ['Accessoires/Maison/Bureau', 'Accueil'],
                'short_description' => 'Chargeur sans fil Qi certifié 15W',
                'description' => 'Base de recharge sans fil Qi certifié avec puissance 15W. Compatible tous smartphones Qi, LED indicateur de charge, design fin et élégant. Protection contre surchauffe et détection automatique des corps étrangers.',
                'images' => ['base-recharge.png'],
                'stock' => 45,
            ],

            // Accessoires / Bagagerie (5 produits)
            [
                'name' => 'Sac à Dos 25L',
                'sku' => 'ACC-SAC-DOS-001',
                'price' => 49.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Bagagerie',
                'short_description' => 'Sac à dos urbain 25L avec compartiment ordinateur',
                'description' => 'Sac à dos 25L en polyester résistant avec compartiment laptop 15 pouces rembourré. Multiples poches de rangement, bretelles ergonomiques ajustables, dos matelassé respirant. Parfait pour le travail, études ou voyages.',
                'images' => ['sac-a-dos.png'],
                'stock' => 55,
            ],
            [
                'name' => 'Sac de Sport 40L',
                'sku' => 'ACC-SAC-SPORT-001',
                'price' => 39.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Bagagerie',
                'short_description' => 'Sac de sport spacieux avec compartiment chaussures',
                'description' => 'Sac de sport 40L en nylon déperlant avec grand compartiment principal et poche séparée pour chaussures. Bretelle ajustable et poignées renforcées, poche extérieure zippée. Idéal pour le gym, piscine et week-ends sportifs.',
                'images' => ['sac-de-sport.png'],
                'stock' => 45,
            ],
            [
                'name' => 'Sac à Cordon',
                'sku' => 'ACC-SAC-CORDON-001',
                'price' => 12.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Bagagerie',
                'short_description' => 'Sac à cordon léger et compact',
                'description' => 'Sac à cordon en polyester résistant, ultra-léger et pliable. Cordons ajustables servant de bretelles, poche intérieure zippée pour objets de valeur. Parfait pour le sport, la piscine ou comme sac d\'appoint.',
                'images' => ['sac-cordon.png'],
                'stock' => 100,
            ],
            [
                'name' => 'Tote Bag Coton Bio',
                'sku' => 'ACC-TOTEBAG-001',
                'price' => 14.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Bagagerie',
                'short_description' => 'Tote bag en coton bio certifié GOTS',
                'description' => 'Tote bag en coton bio 100% certifié GOTS, anses longues renforcées. Grande capacité pour vos courses ou affaires quotidiennes. Lavable en machine, design intemporel et éco-responsable.',
                'images' => ['totebag.png'],
                'stock' => 110,
            ],
            [
                'name' => 'Porte-Monnaie Cuir',
                'sku' => 'ACC-PORTE-MONNAIE-001',
                'price' => 24.90,
                'compare_at_price' => null,
                'category' => 'Accessoires/Bagagerie',
                'short_description' => 'Porte-monnaie compact en cuir véritable',
                'description' => 'Porte-monnaie en cuir véritable avec multiples compartiments pour cartes et billets. Fermeture éclair sécurisée, compartiment monnaie zippé, protection RFID intégrée. Design élégant et compact pour poche ou sac.',
                'images' => ['porte-monnaie.png'],
                'stock' => 70,
            ],

            // Sport (5 produits)
            [
                'name' => 'Raquette de Padel Pro',
                'sku' => 'SPORT-RAQUETTE-001',
                'price' => 89.90,
                'compare_at_price' => null,
                'category' => ['Sport', 'Accueil'],
                'short_description' => 'Raquette de padel professionnelle en fibre de carbone',
                'description' => 'Raquette de padel en fibre de carbone avec noyau EVA haute densité. Équilibre optimal entre puissance et contrôle, surface rugueuse pour effets améliorés. Poids 365g, grip anti-transpiration confortable. Housse de protection incluse.',
                'images' => ['raquette-padel.png'],
                'stock' => 25,
            ],
            [
                'name' => 'Balles de Golf Pack x12',
                'sku' => 'SPORT-BALLE-GOLF-001',
                'price' => 29.90,
                'compare_at_price' => null,
                'category' => 'Sport',
                'short_description' => 'Pack de 12 balles de golf haute performance',
                'description' => 'Pack de 12 balles de golf avec noyau haute compression pour distance maximale. Revêtement uréthane durable, alvéoles aérodynamiques optimisées. Excellente sensation au putting, idéales pour joueurs intermédiaires et avancés.',
                'images' => ['balle-golf.png'],
                'stock' => 40,
            ],
            [
                'name' => 'Tapis de Yoga Antidérapant',
                'sku' => 'SPORT-TAPIS-YOGA-001',
                'price' => 34.90,
                'compare_at_price' => null,
                'category' => ['Sport', 'Accueil'],
                'short_description' => 'Tapis de yoga écologique 6mm antidérapant',
                'description' => 'Tapis de yoga 183x61cm en TPE écologique, épaisseur 6mm pour confort optimal. Surface antidérapante des deux côtés, sans latex ni PVC. Léger et facile à nettoyer, sangle de transport incluse. Parfait pour yoga, pilates et fitness.',
                'images' => ['tapis-yoga.png'],
                'stock' => 60,
            ],
            [
                'name' => 'Gants de Musculation',
                'sku' => 'SPORT-GANTS-001',
                'price' => 19.90,
                'compare_at_price' => null,
                'category' => 'Sport',
                'short_description' => 'Gants de musculation avec protection poignet',
                'description' => 'Gants de musculation avec paumes renforcées et protection poignet intégrée. Tissu respirant et évacuant l\'humidité, fermeture scratch ajustable. Grip antidérapant pour levées de poids en toute sécurité. Compatibles écran tactile.',
                'images' => ['gants-muscu.png'],
                'stock' => 75,
            ],
            [
                'name' => 'Baskets Running Unisexe',
                'sku' => 'SPORT-BASKETS-001',
                'price' => 79.90,
                'compare_at_price' => null,
                'category' => 'Sport',
                'short_description' => 'Baskets running légères avec amorti haute performance',
                'description' => 'Baskets running avec semelle intermédiaire en mousse haute résilience et amorti optimal. Tige mesh respirante, semelle extérieure adhérente multi-surfaces. Design léger 270g, drop 8mm. Parfaites pour courses longues distances et entraînements quotidiens.',
                'images' => ['baskets.png'],
                'stock' => 50,
            ],

        ];

        foreach ($products as $productData) {
            $this->createProduct($productData, $categories);
        }
    }

    /**
     * Create a single product with translations and images
     */
    private function createProduct(array $data, array $categories): void
    {
        $productType = $data['type'] ?? 'simple';
        $hasVariants = isset($data['variants']) && count($data['variants']) > 0;

        // Créer le produit
        $product = Product::create([
            'shop_id' => $this->shopId,
            'sku' => $data['sku'],
            'type' => $productType,
            'is_active' => true,
            'manage_stock' => ! $hasVariants,
            'stock_qty' => $hasVariants ? 0 : ($data['stock'] ?? 0),
            'price' => $data['price'],
            'compare_at_price' => $data['compare_at_price'],
        ]);

        // Créer la traduction
        ProductTranslation::create([
            'product_id' => $product->id,
            'locale' => 'fr',
            'name' => $data['name'],
            'slug' => Str::slug($data['name']),
            'short_description' => $data['short_description'],
            'description' => $data['description'],
            'meta_title' => $data['name'].' - Omersia',
            'meta_description' => $data['short_description'],
        ]);

        // Associer aux catégories (support pour catégorie unique ou multiples)
        $categoryPaths = is_array($data['category']) ? $data['category'] : [$data['category']];

        foreach ($categoryPaths as $categoryPath) {
            if (isset($categories[$categoryPath])) {
                $category = $categories[$categoryPath];
                $product->categories()->syncWithoutDetaching([$category->id]);

                // Associer aussi aux catégories parentes
                $currentCategory = $category;
                while ($currentCategory->parent_id) {
                    $product->categories()->syncWithoutDetaching([$currentCategory->parent_id]);
                    $currentCategory = Category::find($currentCategory->parent_id);
                }
            }
        }

        // Créer les images (copier depuis le dossier de seed vers storage comme le BO)
        foreach ($data['images'] as $index => $imageName) {
            $storedPath = $this->copyImageToStorage($imageName, $product->id);

            if ($storedPath) {
                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $storedPath,
                    'position' => $index,
                    'is_main' => $index === 0,
                ]);
            }
        }

        // Créer les variantes si nécessaire
        if ($hasVariants) {
            $this->createProductVariants($product, $data);
        }
    }

    /**
     * Create product variants with options and values
     */
    private function createProductVariants(Product $product, array $data): void
    {
        // Créer l'option (ex: "Taille")
        $option = \Omersia\Catalog\Models\ProductOption::create([
            'product_id' => $product->id,
            'name' => $data['option_name'] ?? 'Taille',
            'position' => 0,
        ]);

        // Créer les valeurs d'option et les variantes
        foreach ($data['variants'] as $index => $variantData) {
            // Créer la valeur d'option (ex: "S", "M", "L")
            $optionValue = \Omersia\Catalog\Models\ProductOptionValue::create([
                'product_option_id' => $option->id,
                'value' => $variantData['name'],
                'position' => $index,
            ]);

            // Créer la variante
            $variant = \Omersia\Catalog\Models\ProductVariant::create([
                'product_id' => $product->id,
                'sku' => $variantData['sku'],
                'name' => $variantData['name'],
                'is_active' => true,
                'manage_stock' => true,
                'stock_qty' => $variantData['stock'],
                'price' => $variantData['price'] ?? $data['price'],
                'compare_at_price' => $variantData['compare_at_price'] ?? $data['compare_at_price'],
            ]);

            // Associer la valeur d'option à la variante
            $variant->values()->attach($optionValue->id);
        }
    }

    /**
     * Copier une image du dossier de seed vers le storage (simule l'upload du BO)
     */
    private function copyImageToStorage(string $imageName, int $productId): ?string
    {
        $sourcePath = base_path($this->seedImagesPath.'/'.$imageName);

        // Vérifier si le fichier source existe
        if (! file_exists($sourcePath)) {
            $this->command->warn("⚠️  Image not found: {$imageName}");

            return null;
        }

        // Générer un nom unique comme le ferait le BO
        $extension = pathinfo($imageName, PATHINFO_EXTENSION);
        $filename = Str::random(40).'.'.$extension;

        // Créer le chemin de destination avec l'ID du produit séparé par chiffre
        // Exemple: ID 1 -> products/1/, ID 11 -> products/1/1/, ID 123 -> products/1/2/3/
        $idPath = implode('/', str_split((string) $productId));
        $directory = 'products/'.$idPath;
        $destinationPath = $directory.'/'.$filename;

        // Copier le fichier vers le storage public
        $fileContent = file_get_contents($sourcePath);
        Storage::disk('public')->put($destinationPath, $fileContent);

        return $destinationPath;
    }
}
