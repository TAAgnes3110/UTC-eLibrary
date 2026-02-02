<?php

namespace App\Http\Middleware;

use App\Helpers\CurrentUser;
use App\Models\User;
use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class Init
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next)
    {
        global $currentSystem, $currentCustomer, $currentUser, $currentPerson, $role_prefix, $period, $domain, $bearer_token, $yaht, $__token, $apis;
        //Log::info(json_encode($request));
        $bearer_token = '';
        $yaht = '';
        $__token = '';
        try {
            $domain = $request->headers->get('domain');
            $period = $request->headers->get('period', '2025-2026');
            if ($domain) {
                $bearer_token = $request->bearerToken();
                $yaht = request()->headers->get('yaht');
                if ($yaht) {
                    $payload = JWT::decode($yaht, new Key(config('jwt.secret'), 'HS256'));
                    if (!empty($payload->data) && !empty($payload->data->current_user->id)) {
                        $user = User::find($payload->data->current_user->id);
                        if ($user) {
                            Auth::login($user);
                            $currentSystem = new \stdClass();
                            $currentSystem->user_id = $user->id;
                            $currentSystem->customer_code = 'UNK';
                            $currentSystem->system = (object)['value' => 'library'];

                            $currentCustomer = new \stdClass();
                            $currentCustomer->code = 'LIB';

                            $currentPerson = new \stdClass();
                            $currentPerson->id = $user->id;
                            $currentPerson->name = $user->name;

                            $role_prefix = '';
                            $currentUser = new CurrentUser($user);

                            return $next($request);
                        }
                    }
                }
                return response()->json([
                    'status' => 'error',
                    'message' => 'Phần mềm bạn đang sử dụng không tồn tại hoặc đã hết hạn. Vui lòng liên hệ với Admin để được hỗ trợ 3',
                ], 404);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Token hết thời hạn',
            ], 408);
        }
    }
}
