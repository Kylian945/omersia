<?php

declare(strict_types=1);

namespace Omersia\Api\OpenApi;

/**
 * @\OpenApi\Annotations\Info(
 *     version="1.0.0",
 *     title="Omersia Storefront API",
 *     description="Documentation de l'API publique Storefront pour l'e-commerce Omersia"
 * )
 *
 * @\OpenApi\Annotations\Server(
 *     url="/",
 *     description="Environnement local / base"
 * )
 *
 * @\OpenApi\Annotations\SecurityScheme(
 *     securityScheme="api.key",
 *     type="apiKey",
 *     name="X-API-KEY",
 *     in="header",
 *     description="Clé API pour authentification frontend. Requise pour tous les endpoints."
 * )
 * @\OpenApi\Annotations\SecurityScheme(
 *     securityScheme="sanctum",
 *     type="http",
 *     scheme="bearer",
 *     bearerFormat="Token",
 *     description="Token Laravel Sanctum pour utilisateurs authentifiés"
 * )
 *
 * @\OpenApi\Annotations\Tag(
 *     name="Pages",
 *     description="Endpoints liés aux pages CMS"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Produits",
 *     description="Endpoints liés aux produits du catalogue"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Catégories",
 *     description="Endpoints liés aux catégories de produits"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Menus",
 *     description="Endpoints liés aux menus de navigation"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Panier",
 *     description="Endpoints pour la gestion du panier"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Commandes",
 *     description="Endpoints pour la gestion des commandes"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Authentification",
 *     description="Endpoints d'authentification et gestion de compte"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Adresses",
 *     description="Endpoints pour la gestion des adresses client"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Paiement",
 *     description="Endpoints liés aux méthodes de paiement"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Livraison",
 *     description="Endpoints liés aux méthodes de livraison"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Recherche",
 *     description="Endpoints de recherche de produits"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Compte",
 *     description="Endpoints de gestion du compte client"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Checkout",
 *     description="Endpoints pour le processus de commande"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Taxes",
 *     description="Endpoints de calcul des taxes"
 * )
 * @\OpenApi\Annotations\Tag(
 *     name="Thème",
 *     description="Endpoints liés à la personnalisation du thème"
 * )
 */
class OpenApiSpecs {}
