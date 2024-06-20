DB_CONNECTION=sqlite

php artisan migrate

php artisan serve

npm i

composer require laravel/ui
php artisan ui bootstrap
php artisan ui bootstrap --auth

npm i
npm run dev

Vào database/seeders/DatabaseSeeder
  mở khóa: (mật khẩu mặc định password)
       \App\Models\User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

php artisan db:seed

Setup Pusher
  Vào https://pusher.com/
    Đăng ký tài khoản / tạo channel

      composer require pusher/pusher-php-server
    .evn
      PUSHER_APP_ID=1779711
      PUSHER_APP_KEY=eedb7430b2fcd144ab35
      PUSHER_APP_SECRET=159c5dca7862896afa16
      PUSHER_APP_CLUSTER=ap1
      BROADCAST_DRIVER=pusher

      npm i --save-dev laravel-echo pusher-js
    
  Vào resources/js/bootstrap
    uncomment:
        import Echo from 'laravel-echo';
        import Pusher from 'pusher-js';
        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'pusher',
            key: import.meta.env.VITE_PUSHER_APP_KEY,
            cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
            wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
            wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
            wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
            forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
            enabledTransports: ['ws', 'wss'],
        });

    npm run dev

Bài 1: Realtime notification (user login có thông báo)
views/layouts/app.blade
     <main class="py-4">
        <div id="notification" class="alert mx-4 invisible">
            Bạn đã đăng nhập
        </div>
        @yield('content')
    </main>

php artisan make:event UserSessionChange
    app/Events UserSessionChange
        class UserSessionChange implements ShouldBroadcast
        {
            use Dispatchable, InteractsWithSockets, SerializesModels;
            public $message;
            public $type;
            public function __construct($message, $type )
            {
                $this->message = $message;
                $this->type = $type;
            }
            public function broadcastOn()
            {
                 \Log::debug("{$this->message}, {$this->type}");
                return new Channel('notifications');
            }
        }
    
    php artisan make:listener BroadcastUserLoginNotification
        use Illuminate\Auth\Events\Login;
        use Illuminate\Auth\Events\Logout;
        use App\Events\UserSessionChange;

        class BroadcastUserLoginNotification
        {
            public function __construct()
            {
                //
            }
            public function handle(Login $event): void
            {
                broadcast(new UserSessionChange("{$event->user->name} is online", "success"));
            }
        }

    php artisan make:listener BroadcastUserLogoutNotification
        class BroadcastUserLogoutNotification
        {
            public function __construct()
            {
                //
            }
            public function handle(Logout $event): void
            {
                broadcast(new UserSessionChange("{$event->user->name} is offline", "danger"));
            }
        }
    
    App/Providers/EventServiceProvider
        use App\Listeners\BroadcastUserLoginNotification;
        use App\Listeners\BroadcastUserLogoutNotification;
        protected $listen = [
            Registered::class => [
                SendEmailVerificationNotification::class,
            ],
            Login::class => [
                BroadcastUserLoginNotification::class
            ],
            Logout::class => [
                BroadcastUserLogoutNotification::class
            ]
        ];

    resource/js/app.js
        (lưu ý chạy npm run dev)
            Echo.channel("notifications")
                .listen("UserSessionChange", e => {
                    console.log({ e });
                    const notiElement = document.querySelector("#notification")
                    notiElement.innerText = e.message
                    notiElement.classList.remove("invisible")
                    notiElement.classList.remove("alert-success")
                    notiElement.classList.remove("alert-danger")
                    notiElement.classList.add('alert-' + e.type)

                })

    resource/js/bootstrap.js
        cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER,

+Những người không login sẽ không thấy được trạng thái
    config/app.php
        uncomment
            App\Providers\BroadcastServiceProvider::class,
    
    route/channels.php
        Broadcast::channel('notifications', function($user){
            return $user != null;
        });
    
    App\Events\UserSessionChange
        public function broadcastOn()
        {
            // \Log::debug("{$this->message}, {$this->type}");
            return new PrivateChannel('notifications');
        }
    resource/js/app.js
        Echo.private("notifications")
            .listen("UserSessionChange", e => {
                console.log({ e });
                const notiElement = document.querySelector("#notification")
                notiElement.innerText = e.message
                notiElement.classList.remove("invisible")
                notiElement.classList.remove("alert-success")
                notiElement.classList.remove("alert-danger")
                notiElement.classList.add('alert-' + e.type)

            })

# Bài 2: Tạo realtime API
    php artisan make:controller api/UserController -r --model=User

        App\Http\Controllers\api\UserController 
            public function index()
            {
                return User::all();
            }
            public function store(Request $request)
            {
                $data = $request->all();
                $data['password'] = bcrypt($request->password);

                return User::create($data);
            }
            public function show(User $user)
            {
                return $user;
            }
            public function update(Request $request, User $user)
            {
                $data = $request->all();
                $data['password'] = bcrypt($request->password);
                $user->fill($data);
                $user->save();

                return $user;
            }
            public function destroy(User $user)
            {
                $user->delete();
                return $user;
            }
    
    route/api.php
        use App\Http\Controllers\api\UserController;

            use App\Http\Controllers\api\UserController;
            Route::apiResource('users', UserController::class);
    
    Chạy Postman
        http://127.0.0.1:8000/api/users => GET
        http://127.0.0.1:8000/api/users => POST, form-data (name, email, password)
        http://127.0.0.1:8000/api/users/3 => PATCH, x-www-form-urlencoded (name)
        http://127.0.0.1:8000/api/users/3 => DELETE
        
    php artisan make:event UserCreated
        use App\Models\User;
        class UserCreated implements ShouldBroadcast
        {
            use Dispatchable, InteractsWithSockets, SerializesModels;
            public $user;
            public function __construct(User $user)
            {
                $this->user = $user;
            }
            public function broadcastOn()
            {
                \Log::debug("Create {$this->user->name}");
                return new Channel('users');
            }
        }


    php artisan make:event UserUpdated
    php artisan make:event UserDeleted
        Giống thằng UserCreated => Đổi tên class và Log (UserDeleted xóa SerializesModels)


    App\Models\User
        use App\Events\UserCreated;
        use App\Events\UserUpdated;
        use App\Events\UserDeleted;

        protected $dispatchesEvents = [
            'created' => UserCreated::class,
            'updated' => UserUpdated::class,
            'deleted' => UserDeleted::class,
        ];
    
    Tạo view để thấy sự thay đổi
        route/web.php
            Route::view("/users", 'users.showAll')->name('users.all');
        

        views/layouts/app.blade.php
            @stack('styles')
            @stack('scripts')

        views/users/showAll.blade.php
            @extends('layouts.app')
                @section('content')
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">{{ __('Users') }}</div>

                                <div class="card-body">
                                    <ul id="users">

                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                @endsection
                @push('scripts')
                    <script type="module">
                        const userElement = document.getElementById('users')
                        window.axios.get('/api/users')
                            .then(function(response){
                                const users = response.data
                                users.forEach((user, index) => {
                                    const element = document.createElement('li')
                                    element.setAttribute('id', user.id)
                                    element.innerText = user.name
                                    userElement.appendChild(element)
                                })
                            })
                    </script>

                    <script type="module">
                        const userElement = document.getElementById('users')
                        Echo.channel('users')
                            .listen('UserCreated', e => {
                                const element = document.createElement('li')
                                element.setAttribute('id', e.user.id)
                                element.innerText = e.user.name

                                userElement.appendChild(element)
                            })
                            .listen('UserUpdated', e => {
                                const element = document.getElementById(e.user.id)
                                element.innerText = e.user.name
                            })
                            .listen('UserDeleted', e => {
                                const element = document.getElementById(e.user.id)
                                element.parentNode.removeChild(element)
                            })
                    </script>
                @endpush
        


Bài 4: Chat với realtime message với laravel echo
    php artisan make:controller ChatController
        use App\Events\MessageSent;
        class ChatController extends Controller
        {
            public function showChat(){
                return view('chat.show');
            }
            public function messageReceived(Request $req){
                $rules = [
                    'message' => 'required',
                ];
                $req->validate($rules);
                broadcast(new MessageSent($req->user(), $req->message));

                return response()->json('message broadcast');
            }
        }
        
    
    
    route/web.php
        Route::get('/chat', [App\Http\Controllers\ChatController::class, 'showChat'])->name('chat.show');

    view/chat/show.blade.php
        @extends('layouts.app')
            @section('content')
            <div class="container-fluid">
                <div class="row justify-content-center">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">{{ __('Chat') }}</div>

                            <div class="card-body">
                                <div class="row p-2">
                                    <div class="col-10">
                                        <div class="row">
                                            <div class="col-12 border rounded-lg p-3">
                                                <ul id="messages" class="list-unstyled overflow-auto" style="min-height: 45vh">
                                                    
                                                </ul>
                                            </div>
                                            <form>
                                                <div class="row py-3">
                                                    <div class="col-10">
                                                        <input type="text" id="message" class="form-control">
                                                    </div>
                                                    <div class="col-2">
                                                        <button id="send" type="submit" class="btn btn-primary w-100">Gửi</button>
                                                    </div>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                    <div class="col-2">
                                        <p>
                                            <strong>Người dùng Online</strong>
                                            <ul id="users" class="list-unstyled overflow-auto text-info" style="min-height:45vh">
                                                
                                            </ul>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endsection


            @push('scripts')
                <script type="module">
                    const userElement = document.getElementById("users")
                    const messageElement = document.getElementById("messages")

                    Echo.join('chat')
                        .here(users => {
                            // console.log(users)
                            users.forEach((user, index) => {
                                const element = document.createElement('li')
                                element.setAttribute('id',  user.id)
                                element.innerText = user.name
                                userElement.appendChild(element)
                            })
                        })
                        .joining(user => {
                            // console.log(users, 'joining')
                            const element = document.createElement('li')
                            element.setAttribute('id',  user.id)
                            element.innerText = user.name
                            userElement.appendChild(element)
                        })
                        .leaving(user => {
                            // console.log(users, 'leaving')
                            const element = document.getElementById(user.id)
                            element.parentNode.removeChild(element)
                        })
                </script>
            @endpush  

    php artisan make:event MessageSent
        class MessageSent implements ShouldBroadcast
        {
            use Dispatchable, InteractsWithSockets, SerializesModels;


            public $user;
            public $message;

            public function __construct(User $user, $message)
            {
                $this->user = $user;
                $this->message = $message;
            }


            public function broadcastOn()
            {
                return new PresenceChannel('chat');
            }
        }

    route/channels
        Broadcast::channel('chat', function($user){
            if($user != null){
                return ['id' => $user->id, 'name' => $user->name];
            }
            return false;
        });

    Xử lý chat:
        view/chat/show.blade.php   
            .listen('MessageSent', e => {
                const element = document.createElement('li')
                element.innerText = e.user.name + ": " + e.message;
                messageElement.appendChild(element)
            })

            <script type="module">
                const messageElement = document.getElementById("message")
                const sendElement = document.getElementById("send")

                sendElement.addEventListener('click' , function(e) {
                    e.preventDefault();

                    window.axios.post('/chat/message', {
                        message: messageElement.value
                    })

                    messageElement.value = ''
                })
            </script>

Bài 5: Phòng chat dùng private event (gửi tin nhắn chỉ với 1 người dùng)
        php artisan make:event GreetingSent
            class GreetingSent implements ShouldBroadcast
            {
                use Dispatchable, InteractsWithSockets, SerializesModels;

                public $user;
                public $message;

                public function __construct(User $user, $message)
                {
                    $this->user = $user;
                    $this->message = $message;
                }

                public function broadcastOn()
                {   
                    return new PrivateChannel('chat.greet.' . $this->user->id);
                }
            }

        route/channel
            Broadcast::channel('chat.greet.{receiver_id}', function ($user, $receiver_id) {
                return (int) $user->id === (int) $receiver_id;
            });

        ChatController  
            use App\Events\GreetingSent;
            use App\Models\User;

            public function greetReceived(Request $req, User $receiver){
                broadcast(new GreetingSent( $receiver, "{$req->user()->name}: đã chào bạn"));
                broadcast(new GreetingSent( $req->user(), "Bạn đã chào {$receiver->name}"));
                return "Lời chào từ {$req->user()->name} đến {$receiver->name}";
            }

        route/web.php
            Route::post('/chat/greet/{receiver}', [App\Http\Controllers\ChatController::class, 'greetReceived'])->name('chat.greet');
        
        view/show.blade.php

            .here(users => {
                // console.log(users)
                users.forEach((user, index) => {
                    const element = document.createElement('li')
                    element.setAttribute('id',  user.id)
                    element.innerText = user.name

                    // event click
                    element.setAttribute('onclick',  `greetUser('${user.id}')`)

                    userElement.appendChild(element)
                })
            })
            .joining(user => {
                // console.log(users, 'joining')
                const element = document.createElement('li')
                element.setAttribute('id',  user.id)
                element.innerText = user.name

                // event click
                element.setAttribute('onclick',  `greetUser('${user.id}')`)

                userElement.appendChild(element)
            })

            <script type="module">
                const messageElement = document.getElementById("messages")
                Echo.private('chat.greet.{{ auth()->user()->id }}')
                    .listen('GreetingSent', e => {
                        const element = document.createElement('li')
                        element.innerText = e.message
                        element.classList.add('text-success')

                        messageElement.appendChild(element)
                    })
            </script>


