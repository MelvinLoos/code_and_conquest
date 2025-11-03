<?php

namespace App\Controller;

use App\Entity\PlayerCharacter;
use App\Form\CharacterRegistrationFormType;
use App\Repository\CharacterRepository;
use App\Service\CharacterStatsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer, CharacterRepository $characterRepository, CharacterStatsService $statsService, RateLimiterFactory $formRegistrationLimiter): Response
    {
        $character = new PlayerCharacter();
        $form = $this->createForm(CharacterRegistrationFormType::class, $character);
        $form->handleRequest($request);

        // Only check rate limit for GET requests or invalid submissions
        if (!$form->isSubmitted() || !$form->isValid()) {
            $limiter = $formRegistrationLimiter->create($request->getClientIp());
            if (false === $limiter->consume(1)->isAccepted()) {
                $this->addFlash('error', 'You have attempted to register too many times. Please try again later.');
                return $this->render('registration/register.html.twig', [
                    'registrationForm' => $form->createView(),
                    'classes' => $statsService->getAllClassesWithDetails(),
                ], new Response(null, Response::HTTP_TOO_MANY_REQUESTS));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $character->getEmail();
            
            list($local, $domain) = explode('@', $email, 2);
            $local = strtolower(str_replace('.', '', explode('+', $local)[0]));
            $normalizedEmail = $local . '@' . $domain;

            $existingCharacter = $characterRepository->findOneBy(['email' => $normalizedEmail]);
            
            if ($existingCharacter) {
                $form->get('email')->addError(new FormError('A character has already been created with this email address.'));
            } else {
                $class = $form->get('characterClass')->getData();
                $character->setCharacterClass($class);
                $character->setEmail($normalizedEmail);
                $character->setApiKey(bin2hex(random_bytes(30)));
                $character->setStats($statsService->generateStatsForClass($class));

                $em->persist($character);
                $em->flush();

                $emailMessage = (new TemplatedEmail())
                    ->from('no-reply@codeandconquest.com')
                    ->to($character->getEmail())
                    ->subject('Welcome to Code & Conquest!')
                    ->htmlTemplate('email/welcome.html.twig')
                    ->context(['character' => $character]);
                
                $mailer->send($emailMessage);

                return $this->redirectToRoute('app_register_success');
            }
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
            'classes' => $statsService->getAllClassesWithDetails(),
        ], new Response(
            null,
            $form->isSubmitted() && !$form->isValid() ? Response::HTTP_UNPROCESSABLE_ENTITY : Response::HTTP_OK,
        ));
    }

    #[Route('/register/success', name: 'app_register_success')]
    public function registerSuccess(): Response
    {
        return $this->render('registration/success.html.twig');
    }
}