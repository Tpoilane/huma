<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AuthController extends AbstractController
{
    private $entityManager;
    private $passwordHasher;

    public function __construct(EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher)
    {
        $this->entityManager = $entityManager;
		$this->passwordHasher  = $passwordHasher;
    }

    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(Request $request): Response
    {
        $email = $request->get('email'); 
        $password = $request->get('password'); 

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Invalid credentials'], Response::HTTP_UNAUTHORIZED);
        }
		

		$token = bin2hex(random_bytes(32));

        $user->setToken($token );
	
        $this->entityManager->flush();

        return $this->json(['token' => $token]);
    }

    #[Route('/user', name: 'get_user_info', methods: ['GET'])]
    public function getUserInfo(Request $request): Response
    {
        $token = $this->getTokenFromRequest($request);

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['token' => $token]);

        if (!$user) {
            return $this->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
        ]);
    }

    #[Route('/logout', name: 'logout', methods: ['GET'])]
    public function logout(Request $request): Response
    {
		
		$token = $this->getTokenFromRequest($request);


        $user = $this->entityManager->getRepository(User::class)->findOneBy(['token' => $token]);
		
		if (!$user) {
            return $this->json(['message' => 'Invalid token'], Response::HTTP_UNAUTHORIZED);
        }
		
        $user->setToken(null);
        $this->entityManager->flush();
        
		return $this->json(['message' => 'Logged out successfully']);
    }

    private function getTokenFromRequest(Request $request): ?string
    {
		$token = $request->get('token'); 
		if($token) return $token;
        return null;
    }
}
