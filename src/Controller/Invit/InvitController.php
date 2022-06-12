<?php

namespace App\Controller\Invit;

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Services\Group\GroupManagement;
use App\Services\Invit\Invit;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;

class InvitController extends AbstractController
{


    private GroupRepository $groupRepository;
    private UserRepository $userRepository;

    public function __construct(GroupRepository $groupRepository, UserRepository $userRepository)
    {
        $this->groupRepository = $groupRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * @Route("/users/{userEmail}/groupes/{groupId}/sendInvit", name="sendInvit", methods={"GET"})
     */
    public function sendInvit(Request $request): JsonResponse
    {
        $invit = new Invit($this->groupRepository, $this->userRepository);
        $subject = 'Tu as été invité à un groupe Nou !';
        $groupId = $request->get('groupId');
        $eGroup = $this->groupRepository->find($groupId);
        $invitId = Uuid::v6();
        $userEmail = $request->get('userEmail');
        $eUser = $this->userRepository->findOneByEmail($userEmail);
        $userId = $eUser->getId();
        $url = 'http://localhost:8000/users/' . $userId . '/groupes/' . $groupId . '/invites/' . $invitId . '/accept';
        $body = $this->renderView('invitation-accept.html.twig',
        [
            'user' => $eGroup->getAdmin()->getUsername(),
            'group' => $eGroup->getName(),
            'link' =>$url
        ]);
        if ($invit->verifUser($groupId, $userId)) {
            $invit->sendMail($userId, $groupId, $body, $subject, $userEmail);
            return new JsonResponse(
                [
                    'message' => 'Mail envoyé',
                ], 200
            );
        }
        return new JsonResponse(
            [
                'message' => 'Utilisateur deja ajouté'
            ], 400
        );
    }

    /**
     * @Route("/users/{userId}/groupes/{groupId}/invites/{invitId}/accept", name="invit_accept", methods={"GET"})
     */
    public function invitAccept(Request $request): Response
    {
        $group = new GroupManagement($this->groupRepository, $this->userRepository);
        $invit = new Invit($this->groupRepository, $this->userRepository);
        $data = $group->getNames($request->get('groupId'), $request->get('userId'));
        $eGroup = $this->groupRepository->find($request->get('groupId'));
        $body = $this->renderView('accept.html.twig',
            [
                'user' => $data['username'],
                'group' => $eGroup->getName(),
            ]);
        $subject = $data['username'] . ' a accepté ton invitation';
        $userEmail = $eGroup->getAdmin()->getEmail();
        if ($invit->verifUser($request->get('groupId'), $request->get('userId'))) {
            $group->addParticipantInAGroup($data['group_name'], $data['username']);
            $invit->sendMail($request->get('userId'), $request->get('groupId'), $body, $subject, $userEmail);

            return $this->render('invitation-accepted.html.twig', [
                'alreadyAccepted' => false,
                'userSendInvite' => $eGroup->getAdmin()->getUsername(),
                'groupeName' => $eGroup->getName(),
            ]);
        }
        return $this->render('invitation-accepted.html.twig', [
            'alreadyAccepted' => true,
            'userSendInvite' => $eGroup->getAdmin()->getUsername(),
            'groupeName' => $eGroup->getName(),
        ]);
    }

    /**
     * @Route("/users/{userId}/groupes/{groupId}/invites/{invitId}/decline", name="invit_decline", methods={"GET"})
     */
    public function invitDecline(Request $request): JsonResponse
    {
        $invit = new Invit($this->groupRepository, $this->userRepository);
        $eUser = $this->userRepository->find($request->get('userId'));
        $eGroup = $this->groupRepository->find($request->get('groupId'));
        $body = $eUser->getUsername() . ' has declined your invitation on ' . $eGroup->getName() . '\'s group.';
        $subject = $eUser->getUsername() . ' has declined your invitation';
        $userEmail = $eGroup->getAdmin()->getEmail();
        $invit->sendMail($request->get('userId'), $request->get('groupId'), $body, $subject, $userEmail);
        return new JsonResponse(
            [
                'message' => 'Pas acceptée',
                'isAccepted' => false
            ], 200
        );
    }
}
