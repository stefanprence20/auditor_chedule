<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
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

        // Convert the schedule to JSON and return as response
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
        $job->setTitle($data['title']); // Assuming title is passed in the request
        $job->setDescription($data['description']); // Assuming description is passed in the request
        $job->setDate($data['date']); // Assuming date is passed in the request

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
    public function markJobAsCompleted($id): Response
    {
        // Retrieve the job from the database
        $job = $this->getDoctrine()->getRepository(Job::class)->find($id);

        if (!$job) {
            return new Response("Job not found", Response::HTTP_NOT_FOUND);
        }

        // Mark the job as completed
        $job->setCompleted(true);

        // Save the changes to the database
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->flush();

        return new Response("Job marked as completed successfully!", Response::HTTP_OK);
    }
}