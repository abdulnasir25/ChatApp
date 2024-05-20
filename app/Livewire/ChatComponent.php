<?php

namespace App\Livewire;

use App\Models\User;
use App\Models\Message;
use Livewire\Component;
use Livewire\Attributes\On;
use App\Events\MessageSendEvent;

class ChatComponent extends Component
{
    public $user;
    public $senderId;
    public $receiverId;
    public $message = '';
    public $messages = [];

    public function render()
    {
        return view('livewire.chat-component');
    }

    public function mount($user_id)
    {
        $this->senderId = auth()->user()->id; // logged user id
        $this->receiverId = $user_id; // to whome you send message

        $messages = Message::where(function($query) {
            $query->where('sender_id', $this->senderId)
                ->where('receiver_id', $this->receiverId);
        })->orWhere(function($query) {
            $query->where('sender_id', $this->receiverId)
                ->where('receiver_id', $this->senderId);
        })
        ->with('sender:id,name', 'receiver:id,name')
        ->get();

        foreach ($messages as $message) {
            $this->appendChatMessage($message);
        }

        $this->user = User::whereId($user_id)->first();
    }

    public function sendMessage()
    {
        $chatMessage = new Message();

        $chatMessage->sender_id = $this->senderId;
        $chatMessage->receiver_id = $this->receiverId;
        $chatMessage->message = $this->message;
        $chatMessage->save();

        $this->appendChatMessage($chatMessage);

        broadcast(new MessageSendEvent($chatMessage))->toOthers();

        $this->message = '';
    }

    public function appendChatMessage($message)
    {
        $this->messages[] = [
            'id' => $message->id,
            'message' => $message->message,
            'sender' => $message->sender->name,
            'receiver' => $message->receiver->name,
        ];
    }

    // listener to listen the message
    // yaha par senderId esly define ki hai, k es id k thorugh message ko listen kya jata hai, jo k logged user hai, awr wo sender hai.
    #[On('echo-private:chat-channel.{senderId},MessageSendEvent')]
    public function listenForMessage($event)
    {
        // convert the array to obj, by fetching the first record.
        $chatMessage = Message::whereId($event['message']['id'])
                        ->with('sender:id,name', 'receiver:id,name')
                        ->first();

        $this->appendChatMessage($chatMessage);
    }
}
