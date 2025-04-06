<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;

class TriviaController extends AbstractController
{
    private $httpClient;
    private $logger;

    public function __construct(HttpClientInterface $httpClient, LoggerInterface $logger)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger;
    }

    #[Route('/forum/trivia', name: 'forum_trivia', methods: ['GET'])]
    public function trivia(): Response
    {
        try {
            // Fetch 10 trivia questions from the Video Games category (category 15)
            $response = $this->httpClient->request('GET', 'https://opentdb.com/api.php', [
                'query' => [
                    'amount' => 10,
                    'category' => 15, // Video Games category
                    'type' => 'multiple', // Multiple choice questions
                ],
            ]);

            $data = $response->toArray();
            $questions = $data['results'] ?? [];

            // Process questions to decode HTML entities and shuffle answers
            foreach ($questions as &$question) {
                $question['question'] = html_entity_decode($question['question'], ENT_QUOTES, 'UTF-8');
                $question['correct_answer'] = html_entity_decode($question['correct_answer'], ENT_QUOTES, 'UTF-8');
                $question['incorrect_answers'] = array_map(function ($answer) {
                    return html_entity_decode($answer, ENT_QUOTES, 'UTF-8');
                }, $question['incorrect_answers']);

                // Combine correct and incorrect answers, then shuffle
                $question['all_answers'] = array_merge(
                    [$question['correct_answer']],
                    $question['incorrect_answers']
                );
                shuffle($question['all_answers']);
            }

            return $this->render('forum/trivia.html.twig', [
                'triviaQuestions' => $questions,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Error fetching trivia questions', [
                'error' => $e->getMessage(),
            ]);
            $this->addFlash('error', 'Unable to load trivia questions. Please try again later.');
            return $this->redirectToRoute('forum_topics');
        }
    }

    #[Route('/api/trivia/validate', name: 'api_trivia_validate', methods: ['POST'])]
    public function validateAnswer(Request $request): JsonResponse
    {
        $questionIndex = $request->request->get('questionIndex');
        $selectedAnswer = $request->request->get('selectedAnswer');
        $correctAnswer = $request->request->get('correctAnswer');

        if ($questionIndex === null || $selectedAnswer === null || $correctAnswer === null) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing required parameters',
            ], 400);
        }

        $isCorrect = $selectedAnswer === $correctAnswer;

        return new JsonResponse([
            'success' => true,
            'isCorrect' => $isCorrect,
            'correctAnswer' => $correctAnswer,
        ]);
    }
}