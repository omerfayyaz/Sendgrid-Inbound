<?php
namespace App\Http\Controllers;

use App\Models\HitCount;
use App\Models\Post;
use App\Models\Response;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use SendGrid\Mail\From;
use SendGrid\Mail\Mail;
use SendGrid\Mail\To;

class PostsController extends Controller
{
        public function index() {
            $posts = Post::paginate(15);
            $data = [
                "posts" => $posts
            ];
            return view("posts", $data);
        }

        public function show($id) {
            $post = Post::findOrFail($id);
            $data = [
                "post" => $post
            ];
            return view("post", $data);
        }

        public function receiveEmailResponse(Request $request) {

            // HitCount::where('id', '1')->increment('count');

            $from = $request->input("from");
            $to = $request->input("to");
            $body = $request->input("text");

            HitCount::create([
                'from' => $from,
                'to' => $to,
                'text' => $request
            ]);

            preg_match("#<(.*?)>#", $from, $sender);
            preg_match("#<(.*?)>#", $to, $recipient);

            // dd($from, $to, $body, $sender, $recipient);

            $senderAddr = $sender[1];
            $recipientAddr = $recipient[1];

            // extract the number between "+" and "@" in the email address, this would be the post ID
            preg_match("#\+(.*?)@#", $recipientAddr, $postId);
            if ($post = Post::find((int)$postId[1])) {
                $comment = Response::create([
                    'email' => $senderAddr,
                    'post_id' => $post->id,
                    'body' => $body
                ]);
                Log::info("Create response: " . $comment->toJson(JSON_PRETTY_PRINT));
            }

            // in any case, return a 200 OK response so SendGrid knows we are done.
            return response()->json(["success" => true]);
        }

        public function sendMails($id) {
            $post = Post::findOrFail($id);

            $mails = [
                "replies@emailtest.vividsol.dev",
            ];
            $subject = "SG Inbound Tutorial: ".$post->title;
            $from = "replies+".$post->id."@emailtest.vividsol.dev";
            $text = "Reply to this email to leave a comment on " . $post->title;

            $mail = new Mail();
            $sender = new From($from, "SG Inbound Tutorial");
            $recipients = [];
            foreach ($mails as $addr) {
                $recipients[] = new To($addr);
            }
            $mail->setFrom($sender);
            $mail->setSubject($subject);
            $mail->addTos($recipients);
            $mail->addContent("text/plain", $text);
            $sg = new \SendGrid(getenv('SENDGRID_API_KEY'));
            try {
                $response = $sg->send($mail);
                $context = json_decode($response->body());
                if ($response->statusCode() == 202) {
                    echo "Emails have been sent out successfully!";
                }else {
                    echo "Failed to send email";
                    Log::error("Failed to send email", ["context" => $context]);
                }
            } catch (\Exception $e) {
                Log::error($e);
            }
        }
}
