<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Events\MessageSent;

use App\Events\GreetingSent;
use App\Models\User;

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

    public function greetReceived(Request $req, User $receiver){
        broadcast(new GreetingSent( $receiver, "{$req->user()->name}: đã chào bạn"));
        broadcast(new GreetingSent( $req->user(), "Bạn đã chào {$receiver->name}"));
        return "Lời chào từ {$req->user()->name} đến {$receiver->name}";
    }
}