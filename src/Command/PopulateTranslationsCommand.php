<?php

namespace App\Command;

use App\Repository\QuestionsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:populate-translations',
    description: 'Populates translation entries for existing questions.'
)]
class PopulateTranslationsCommand extends Command
{
    private $questionsRepository;
    private $entityManager;

    public function __construct(QuestionsRepository $questionsRepository, EntityManagerInterface $entityManager)
    {
        $this->questionsRepository = $questionsRepository;
        $this->entityManager = $entityManager;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $questions = $this->questionsRepository->findAll();
        $connection = $this->entityManager->getConnection();
        $locales = ['en', 'fr', 'es'];

        foreach ($questions as $question) {
            $questionId = $question->getQuestionId();
            $title = $question->getTitle();
            $content = $question->getContent();

            $titleKey = 'question.title.' . $questionId;
            $contentKey = 'question.content.' . $questionId;

            // Current timestamp formatted for MySQL
            $now = (new \DateTime())->format('Y-m-d H:i:s');

            // Check and create trans_unit for title
            $titleUnitId = $connection->fetchOne('SELECT id FROM trans_unit WHERE key_name = ?', [$titleKey]);
            if (!$titleUnitId) {
                $connection->executeStatement(
                    'INSERT INTO trans_unit (key_name, domain, created_at, updated_at) VALUES (?, ?, ?, ?)',
                    [$titleKey, 'messages', $now, $now]
                );
                $titleUnitId = $connection->lastInsertId();
            }

            // Check and create trans_unit for content
            $contentUnitId = $connection->fetchOne('SELECT id FROM trans_unit WHERE key_name = ?', [$contentKey]);
            if (!$contentUnitId) {
                $connection->executeStatement(
                    'INSERT INTO trans_unit (key_name, domain, created_at, updated_at) VALUES (?, ?, ?, ?)',
                    [$contentKey, 'messages', $now, $now]
                );
                $contentUnitId = $connection->lastInsertId();
            }

            // Populate translations for all locales
            foreach ($locales as $locale) {
                // Title
                $existingTitleTranslation = $connection->fetchOne(
                    'SELECT id FROM trans_translation WHERE trans_unit_id = ? AND locale = ?',
                    [$titleUnitId, $locale]
                );
                if (!$existingTitleTranslation) {
                    $connection->executeStatement(
                        'INSERT INTO trans_translation (trans_unit_id, locale, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                        [$titleUnitId, $locale, $title, $now, $now]
                    );
                }

                // Content
                $existingContentTranslation = $connection->fetchOne(
                    'SELECT id FROM trans_translation WHERE trans_unit_id = ? AND locale = ?',
                    [$contentUnitId, $locale]
                );
                if (!$existingContentTranslation) {
                    $connection->executeStatement(
                        'INSERT INTO trans_translation (trans_unit_id, locale, content, created_at, updated_at) VALUES (?, ?, ?, ?, ?)',
                        [$contentUnitId, $locale, $content, $now, $now]
                    );
                }
            }

            $output->writeln("Populated translations for question ID: $questionId");
        }

        $output->writeln('Translation population completed.');
        return Command::SUCCESS;
    }
}