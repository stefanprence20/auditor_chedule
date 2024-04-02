<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;
use App\Entity\Auditor;
use App\Entity\Job;

class AuditorController extends AbstractController
{
    /**
     * @Route("/api/auditors/{id}/schedule", methods={"GET"})
     */
    public function getSchedule($id): Response
    {
        // Retrieve the auditor from the database
        $auditor = $this->getDoctrine()->getRepository(Auditor::class)->find($id);

        if (!$auditor) {
            return new Response("Auditor not found!", Response::HTTP_NOT_FOUND);
        }

        // Get the auditor's schedule
        $schedule = $auditor->getSchedule();

        return $this->json($schedule);
    }

    /**
     * @Route("/api/auditors/{id}/jobs", methods={"POST"})
     */
    public function assignJob(Request $request, $id): Response
    {
        $data = json_decode($request->getContent(), true);

        // Retrieve the auditor from the database
        $auditor = $this->getDoctrine()->getRepository(Auditor::class)->find($id);

        if (!$auditor) {
            return new Response("Auditor not found!", Response::HTTP_NOT_FOUND);
        }

        // Create a new job
        $job = new Job();
        $job->setTitle($data['title']);
        $job->setDescription($data['description']);
        $job->setDate($data['date']);

        // Assign the job to the auditor
        $auditor->addJob($job);

        // Save the changes to the database
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($job);
        $entityManager->flush();

        return new Response("Job assigned successfully!", Response::HTTP_CREATED);
    }

    /**
     * @Route("/api/jobs/{id}/complete", methods={"PUT"})
     */
    public function markJobAsCompleted(Request $request, $id, SerializerInterface $serializer): Response
    {
        // Retrieve the job from the database
        $entityManager = $this->getDoctrine()->getManager();
        $job = $entityManager->getRepository(Job::class)->find($id);

        if (!$job) {
            return new Response("Job not found", Response::HTTP_NOT_FOUND);
        }

        // Mark the job as completed
        $job->setCompleted(true);

        // If assessment is provided, set it for the job
        $data = json_decode($request->getContent(), true);
        if (isset($data['assessment'])) {
            $job->setAssessment($data['assessment']);
        }

        // Handle time zone conversion based on auditor's location
        $auditor = $job->getAuditor();
        $timezone = new \DateTimeZone($auditor->getTimezone());
        $now = new \DateTime('now', $timezone);
        $job->setCompletedAt($now);

        // Save the changes to the database
        $entityManager->flush();

        // Serialize the updated job and return as response
        $json = $serializer->serialize($job, 'json');

        return new Response($json, Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}