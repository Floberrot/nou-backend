<?php

namespace App\Controller\Invit;

use App\Repository\GroupRepository;
use App\Repository\UserRepository;
use App\Services\Group\GroupManagement;
use App\Services\Invit\Invit;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

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
     * @Route("/users/{userId}/groupes/{groupId}/sendInvit", name="sendInvit", methods={"POST"})
     */
    public function sendInvit(Request $request): JsonResponse
    {
        $invit = new Invit($this->groupRepository, $this->userRepository);
        if ($invit->verifUser($request->get('groupId'), $request->get('userId')))
        {
            $invit->sendMail($request->get('userId'), $request->get('groupId'));
            return new JsonResponse(
                [
                    'message' => 'Mail sent',
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
    public function invitAccept(Request $request)
    {
        $group = new GroupManagement($this->groupRepository, $this->userRepository);
        $invit = new Invit($this->groupRepository, $this->userRepository);
        $data = $group->getNames($request->get('groupId'), $request->get('userId'));
        if ($invit->verifUser($request->get('groupId'), $request->get('userId')))
        {
            $group->addParticipantInAGroup($data['group_name'], $data['username']);
            return new JsonResponse(
                [
                    'message' => 'Utilisateur ajouté BG',
                ], 200
            );
        }
        return new JsonResponse(
            [
                'message' => 'Deja ajouté',
            ], 400
        );

    }

    /**
     * @Route("/users/{userId}/groupes/{groupId}/invites/{invitId}/decline", name="invit_decline", methods={"GET"})
     */
    public function invitDecline(Request $request): JsonResponse
    {
        return new JsonResponse(
            [
                'message' => 'Pas acceptée',
                'isAccepted' => false
            ], 200
        );
    }


}
