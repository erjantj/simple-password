<?php 
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\App;

class CorsMiddleware 
{
    protected $settings = array(
        'allowMethods' => 'GET, HEAD, POST, DELETE, PATCH, OPTIONS',
        'allowHeaders' => 'Origin, Content-Type, Accept, Authorization',
        'allowCredentials' => true
    );

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) 
    {
        if ($request->isMethod('OPTIONS')) 
        {
            $response = new Response("", 200);
            $this->setCorsHeaders($request);
            return $response;
        }

        $this->setCorsHeaders($request);
        return $next($request);
    }

    protected function setOrigin($request) 
    {
        $config = config('app');
        $origin = $config['app_origin'];
        $originDev = $config['app_dev_origin'];
        $referer = $request->headers->get('referer');
        if (empty($referer)) {
            $referer = $request->headers->get('origin');
        }

        $host = '';

        if (App::environment(['test', 'local'])) 
        {
            $urlList = parse_url($referer);

            $host = sprintf('%s://%s:%s', 
                isset($urlList['scheme'])?$urlList['scheme']:'',
                isset($urlList['host'])?$urlList['host']:'',
                isset($urlList['port'])?$urlList['port']:''
            );    


            if ($host == $originDev) 
            {
                $origin = $originDev;
            }
        }
        else
        {
            if (is_callable($origin)) 
            {
                // Call origin callback with request origin
                $origin = call_user_func($origin,$request->header("Origin"));
            }    
        }

        header("Access-Control-Allow-Origin: $origin");
    }

    protected function setExposeHeaders($request) 
    {
        if (isset($this->settings->exposeHeaders)) 
        {
            $exposeHeaders = $this->settings->exposeHeaders;
            if (is_array($exposeHeaders)) 
            {
                $exposeHeaders = implode(", ", $exposeHeaders);
            }
            
            header("Access-Control-Expose-Headers: $exposeHeaders");
        }
    }

    protected function setMaxAge($request) 
    {
        if (isset($this->settings['maxAge'])) 
        {
            header("Access-Control-Max-Age: ".$this->settings['maxAge']);
        }
    }
    
    protected function setAllowCredentials($request) 
    {
        if (isset($this->settings['allowCredentials']) && $this->settings['allowCredentials'] === True) 
        {
            header("Access-Control-Allow-Credentials: true");
            header("Access-Control-Expose-Headers: true");
        }
    }
    
    protected function setAllowMethods($request) 
    {
        if (isset($this->settings['allowMethods'])) 
        {
            $allowMethods = $this->settings['allowMethods'];
            if (is_array($allowMethods)) 
            {
                $allowMethods = implode(", ", $allowMethods);
            }
            
            header("Access-Control-Expose-Headers: $allowMethods");
        }
    }

    protected function setAllowHeaders($request) 
    {
        if (isset($this->settings['allowHeaders'])) 
        {
            $allowHeaders = $this->settings['allowHeaders'];
            if (is_array($allowHeaders)) 
            {
                $allowHeaders = implode(", ", $allowHeaders);
            }
        }
        else 
        {  // Otherwise, use request headers
            $allowHeaders = $request->header("Access-Control-Request-Headers");
        }
        
        if (isset($allowHeaders)) 
        {
            header("Access-Control-Allow-Headers: $allowHeaders");
        }
    }

    protected function setCorsHeaders($request) 
    {
        // http://www.html5rocks.com/static/images/cors_server_flowchart.png
        // Pre-flight
        if ($request->isMethod('OPTIONS')) 
        {
            $this->setAllowMethods($request);
        }

        $this->setExposeHeaders($request);
        $this->setAllowCredentials($request);
        $this->setAllowHeaders($request);
        $this->setMaxAge($request);
        $this->setOrigin($request);
    }
}