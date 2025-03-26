<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Form\EvenementType;

use App\Repository\EvenementRepository;
use App\Service\ChatGPTService;
use Doctrine\ORM\EntityManagerInterface;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
#[Route("/evenement")]
final class EvnementController extends AbstractController
{
    #[Route('/all',name: 'evenement_list')]
    public function gettAll(EvenementRepository $repo):Response{
        $evenements = $repo->findAll();
        return $this->render('evenement/listeEvenementadmin.html.twig', [
            "evenements"=>$evenements,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
    #[Route('/add', name: 'evenement_ajouter')]
    public function ajouter(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoFile')->getData();
    
            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();
    
                try {
                    $photoFile->move('C:/xampp/htdocs/img', $newFilename);
                    $evenement->setPhotoEvent($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('evenement_ajouter');
                }
            }
    
            $entityManager->persist($evenement);
            $entityManager->flush();
    
            $this->addFlash('success', 'Événement ajouté avec succès !');
            return $this->redirectToRoute('evenement_list');
        }
    
        return $this->render('evenement/ajouterEvenement.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    #[Route('/delete/{id}', name: 'evenement_supprimer')]
    public function supprimer(int $id, EvenementRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $evenement = $repo->find($id);
    
        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        $entityManager->remove($evenement);
        $entityManager->flush();

        $this->addFlash('success', 'Événement supprimé avec succès !');
        return $this->redirectToRoute('evenement_list');
}
#[Route('/edit/{id}', name: 'evenement_modifier')]
    public function modifier(int $id, Request $request, EvenementRepository $repo, EntityManagerInterface $entityManager): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $photoFile */
            $photoFile = $form->get('photoFile')->getData();

            if ($photoFile) {
                $newFilename = uniqid().'.'.$photoFile->guessExtension();

                try {
                    $photoFile->move('C:/xampp/htdocs/img', $newFilename);
                    $evenement->setPhotoEvent($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du fichier.');
                    return $this->redirectToRoute('evenement_modifier', ['id' => $id]);
                }
            }

            $entityManager->persist($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'Événement modifié avec succès !');
            return $this->redirectToRoute('evenement_list');
        }

        return $this->render('evenement/modifierEvenement.html.twig', [
            'form' => $form->createView(),
            'evenement' => $evenement,
        ]);
    }
    #[Route('/show/{id}', name: 'evenement_detailles')]
    public function Detaills(int $id, EvenementRepository $repo): Response
    {
        $evenement = $repo->find($id);

        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }

        return $this->render('evenement/DetailsEvenementAdmin.html.twig', [
            'evenement' => $evenement,
            'image_base_url' => $this->getParameter('image_base_url'),
        ]);
    }
    #[Route('/qrcode/{id}', name: 'evenement_qrcode')]
    public function generateQrCode(int $id, EvenementRepository $repo): Response
    {
        $evenement = $repo->find($id);
        if (!$evenement) {
            $this->addFlash('error', 'Événement non trouvé.');
            return $this->redirectToRoute('evenement_list');
        }
    
        $baseUrl = $this->getParameter('git_url');
        $url = $baseUrl . '?' .
        http_build_query([
            'id' => $evenement->getId(),
            'nom' => $evenement->getNomEvent(),
            'date' => $evenement->getDateEvent()->format('Y-m-d H:i'),
            'lieu' => $evenement->getLieuEvent(),
            'places' => $evenement->getMaxPlacesEvent(),
        ]);
        $qrCode = new QrCode($url);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        return new Response(
            $result->getString(),
            Response::HTTP_OK,
            ['Content-Type' => 'image/png']
        );
    }
    #[Route('/chatbot', name: 'chatbot', methods: ['POST'])]
    public function index(Request $request, ChatGPTService $chatGPTService): Response
    {
        $message = $request->request->get('message');

        if ($message) {
            $response = $chatGPTService->getResponse($message);
            if ($request->isXmlHttpRequest()) {
                // Retourner uniquement la réponse texte pour AJAX
                return new Response($response);
            }
            // Cas non-AJAX (facultatif)
            return $this->render('evenement/chatbot.html.twig', [
                'message' => $message,
                'response' => $response,
            ]);
        }

        return new Response('Aucun message fourni', 400);
    }
}

