<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ContactController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|min:2|max:100',
            'email' => 'required|email|max:255',
            'phone' => 'nullable|string|max:20',
            'message' => 'required|string|min:10|max:1000',
            'captcha_answer' => 'required|integer',
            'captcha_question' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        // Verificar captcha simple
        $captchaValid = $this->validateCaptcha(
            $request->captcha_question, 
            $request->captcha_answer
        );

        if (!$captchaValid) {
            return response()->json([
                'success' => false,
                'errors' => ['captcha_answer' => ['La respuesta del captcha es incorrecta.']]
            ], 422);
        }

        try {
            $contact = Contact::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'message' => $request->message,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent()
            ]);

            return response()->json([
                'success' => true,
                'message' => '¡Gracias por tu mensaje! Te contactaremos pronto.',
                'contact_id' => $contact->id
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al enviar el mensaje. Por favor intenta nuevamente.'
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $query = Contact::query();

        if ($request->has('unread_only') && $request->unread_only) {
            $query->unread();
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('message', 'like', "%{$search}%");
            });
        }

        $contacts = $query->orderBy('created_at', 'desc')
                         ->paginate($request->get('per_page', 15));

        return response()->json($contacts);
    }

    public function show(Contact $contact)
    {
        // Marcar como leído cuando se visualiza
        if (!$contact->is_read) {
            $contact->markAsRead();
        }

        return response()->json($contact);
    }

    public function markAsRead(Contact $contact)
    {
        $contact->markAsRead();
        
        return response()->json([
            'success' => true,
            'message' => 'Mensaje marcado como leído.'
        ]);
    }

    public function markAsUnread(Contact $contact)
    {
        $contact->markAsUnread();
        
        return response()->json([
            'success' => true,
            'message' => 'Mensaje marcado como no leído.'
        ]);
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Mensaje eliminado correctamente.'
        ]);
    }

    public function generateCaptcha()
    {
        $num1 = rand(1, 10);
        $num2 = rand(1, 10);
        $operators = ['+', '-', '*'];
        $operator = $operators[array_rand($operators)];
        
        $question = "{$num1} {$operator} {$num2}";
        
        switch ($operator) {
            case '+':
                $answer = $num1 + $num2;
                break;
            case '-':
                $answer = $num1 - $num2;
                break;
            case '*':
                $answer = $num1 * $num2;
                break;
        }

        return response()->json([
            'question' => $question,
            'answer' => $answer // Solo para desarrollo, en producción no enviar
        ]);
    }

    private function validateCaptcha($question, $userAnswer)
    {
        // Extraer números y operador de la pregunta
        if (preg_match('/(\d+)\s*([+\-*])\s*(\d+)/', $question, $matches)) {
            $num1 = (int)$matches[1];
            $operator = $matches[2];
            $num2 = (int)$matches[3];
            
            switch ($operator) {
                case '+':
                    $correctAnswer = $num1 + $num2;
                    break;
                case '-':
                    $correctAnswer = $num1 - $num2;
                    break;
                case '*':
                    $correctAnswer = $num1 * $num2;
                    break;
                default:
                    return false;
            }
            
            return (int)$userAnswer === $correctAnswer;
        }
        
        return false;
    }

    public function getStats()
    {
        $total = Contact::count();
        $unread = Contact::unread()->count();
        $thisWeek = Contact::where('created_at', '>=', now()->subWeek())->count();
        $thisMonth = Contact::where('created_at', '>=', now()->subMonth())->count();

        return response()->json([
            'total' => $total,
            'unread' => $unread,
            'this_week' => $thisWeek,
            'this_month' => $thisMonth
        ]);
    }
}