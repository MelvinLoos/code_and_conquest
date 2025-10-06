<?php

namespace App\Controller;

use App\Entity\Character;
use App\Form\CharacterRegistrationFormType;
use App\Repository\CharacterRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register')]
    public function register(Request $request, EntityManagerInterface $em, MailerInterface $mailer, CharacterRepository $characterRepository): Response
    {
        $character = new Character();
        $form = $this->createForm(CharacterRegistrationFormType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $character->getEmail();
            
            list($local, $domain) = explode('@', $email, 2);
            $local = strtolower(str_replace('.', '', explode('+', $local)[0]));
            $normalizedEmail = $local . '@' . $domain;

            $existingCharacter = $characterRepository->findOneBy(['email' => $normalizedEmail]);
            
            if ($existingCharacter) {
                $form->get('email')->addError(new FormError('A character has already been created with this email address.'));
            } else {
                $character->setEmail($normalizedEmail);
                $character->setApiKey(bin2hex(random_bytes(30)));
                $character->setStats([
                    'interface'    => random_int(10, 18),
                    'analytics'    => random_int(8, 15),
                    'sysKnowledge' => random_int(8, 15),
                    'secOps'       => random_int(8, 15),
                    'peopleSkills' => random_int(8, 15),
                ]);

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