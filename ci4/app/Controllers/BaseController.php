<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * BaseController provides a convenient place for loading components
 * and performing functions that are needed by all your controllers.
 *
 * Extend this class in any new controllers:
 * ```
 *     class Home extends BaseController
 * ```
 *
 * For security, be sure to declare any new methods as protected or private.
 */
abstract class BaseController extends Controller
{
    /**
     * Be sure to declare properties for any property fetch you initialized.
     * The creation of dynamic property is deprecated in PHP 8.2.
     */

    // protected $session;

    /**
     * @return void
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        // Load here all helpers you want to be available in your controllers that extend BaseController.
        // Caution: Do not put the this below the parent::initController() call below.
        // $this->helpers = ['form', 'url'];

        // Caution: Do not edit this line.
        parent::initController($request, $response, $logger);

        // Preload any models, libraries, etc, here.
        // $this->session = service('session');
    }

    /**
     * Helper method: Get a nullable POST value (returns null if empty string)
     */
    protected function nullablePost(string $key): ?string
    {
        $value = trim((string) $this->request->getPost($key, ''));
        return $value === '' ? null : $value;
    }

    /**
     * Helper method: Get a nullable decimal POST value (returns null if empty or 0.0)
     */
    protected function nullableDecimalPost(string $key): ?float
    {
        $value = trim((string) $this->request->getPost($key, ''));
        if ($value === '') {
            return null;
        }
        
        $decimal = (float) $value;
        return $decimal === 0.0 ? null : $decimal;
    }
}
