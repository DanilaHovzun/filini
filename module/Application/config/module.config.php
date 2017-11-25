<?php
/**
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2016 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application;

use Zend\Router\Http\Literal;
use Zend\Router\Http\Segment;
use Zend\ServiceManager\Factory\InvokableFactory;

return [
    'router' => [
        'routes' => [
            'home' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/',
                    'defaults' => [
                        'controller' => Controller\IndexController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application_contact' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/contacts',
                    'defaults' => [
                        'controller' => Controller\ContactController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application_catalog' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/catalog',
                    'defaults' => [
                        'controller' => Controller\CatalogController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application_designers' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/designers',
                    'defaults' => [
                        'controller' => Controller\DesignersController::class,
                        'action'     => 'index',
                    ],
                ],
            ],
            'application_product' => [
                'type' => Literal::class,
                'options' => [
                    'route'    => '/product',
                    'defaults' => [
                        'controller' => Controller\ProductController::class,
                        'action'     => 'view',
                    ],
                ],
            ],
            'application_work_list' => [
                'type' => Literal::class,
                'type' => Segment::class,
                'options' => [
                    'route' => '/works',
                    'defaults' => [
                        'controller' => Controller\WorkController::class,
                        'action'     => 'list',
                    ],
                ],
             ],
            'application_work_view' => [
                'type' => Segment::class,
                'options' => [
                    'route' => '/work[/:slug]',
                    'defaults' => [
                        'controller' => Controller\WorkController::class,
                        'action' => 'view',
                        'slug' => '[-a-z0-9]*',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'factories' => [
            Controller\IndexController::class => InvokableFactory::class,
            Controller\ContactController::class => InvokableFactory::class,
            Controller\CatalogController::class => InvokableFactory::class,
            Controller\WorkController::class => InvokableFactory::class,
            Controller\DesignersController::class => InvokableFactory::class,
            Controller\ProductController::class => InvokableFactory::class,
        ],
    ],
    'view_manager' => [
        'display_not_found_reason' => true,
        'display_exceptions'       => true,
        'doctype'                  => 'HTML5',
        'not_found_template'       => 'error/404',
        'exception_template'       => 'error/index',
        'template_map' => [
            'layout/layout'           => __DIR__ . '/../view/layout/layout.phtml',
            'application/index/index' => __DIR__ . '/../view/application/index/index.phtml',
            'error/404'               => __DIR__ . '/../view/error/404.phtml',
            'error/index'             => __DIR__ . '/../view/error/index.phtml',
        ],
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
];
