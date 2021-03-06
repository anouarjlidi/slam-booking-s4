<?php
namespace App\Controller;

use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;

use Psr\Log\LoggerInterface;

use App\Entity\Constants;
use App\Entity\UserContext;
use App\Entity\UserFile;
use App\Entity\Label;
use App\Entity\Resource;
use App\Entity\Note;
use App\Entity\Planification;
use App\Entity\PlanificationPeriod;
use App\Entity\PlanificationLine;
use App\Entity\Timetable;
use App\Entity\TimetableLine;
use App\Entity\Booking;
use App\Entity\BookingLine;
use App\Entity\BookingUser;
use App\Entity\BookingLabel;

use App\Form\NoteType;
use App\Api\BookingApi;

class BookingController extends Controller
{
	// CREATION DES RESERVATIONS
    // Création de réservation
    /**
     * @Route("/bookingmany/create/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function many_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
	{
	return BookingController::create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 1);
	}
    
    /**
     * @Route("/bookingone/create/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function one_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 0);
    }
    
	// Création de réservation
	public function create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$nRepository = $em->getRepository(Note::Class);
	BookingApi::getBookingLinesUrlBeginningAndEndPeriod($em, $timetableLinesList, $beginningDate, $beginningTimetableLine, $endDate, $endTimetableLine);
	// Utilisateurs
	$userFiles = BookingApi::getUserFiles($em, $userFileIDList);
	// Etiquettes
	$labels = BookingApi::getLabels($em, $labelIDList);
	// Note
	$note = new Note($connectedUser);
	if ($noteID > 0) {
		$note = $nRepository->find($noteID);
	}
	return $this->render('booking/create.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource,
			'date' => $date, 'timetableLinesList' => $timetableLinesList, 'beginningDate' => $beginningDate, 'beginningTimetableLine' => $beginningTimetableLine,
			'endDate' => $endDate, 'endTimetableLine' => $endTimetableLine, 'userFiles' => $userFiles, 'userFileIDList' => $userFileIDList,
			'labels' => $labels, 'labelIDList' => $labelIDList, 'noteID' => $noteID, 'note' => $note));
    }

	// Mise a jour de la periode de fin (en création de réservation)
    /**
     * @Route("/bookingmany/endperiodcreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{firstDateNumber}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_end_period_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_end_period_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::end_period_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID, 1);
    }

    // Mise a jour de la periode de fin (en création de réservation)
    /**
     * @Route("/bookingone/endperiodcreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{firstDateNumber}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_end_period_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_end_period_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::end_period_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID, 0);
    }

    // Mise a jour de la periode de fin (en création de réservation)
    public function end_period_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID, $many)
    {
    $connectedUser = $this->getUser();
    $em = $this->getDoctrine()->getManager();
    $userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$cellArray  = explode("-", $timetableLinesList);
    $ttlRepository = $em->getRepository(TimetableLine::Class);
	list($beginningDateString, $beginningTimetableID, $beginningTimetableLineID) = explode("+", $cellArray[0]);
	$beginningDate = date_create_from_format("Ymd", $beginningDateString);
	$beginningTimetableLine = $ttlRepository->find($beginningTimetableLineID);
    $endPeriods = BookingApi::getEndPeriods($em, $planificationPeriod, $resource, $beginningDate, $beginningTimetableLine, 0, $firstDateNumber, $nextFirstDateNumber);
	// Calucl du premier jour affiché précedent
	$previousFirstDateNumber = ($firstDateNumber < Constants::MAXIMUM_NUMBER_BOOKING_DATES_DISPLAYED) ? 0 : ($firstDateNumber - Constants::MAXIMUM_NUMBER_BOOKING_DATES_DISPLAYED);

	return $this->render('booking/period.end.create.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource, 'date' => $date, 'timetableLinesList' => $timetableLinesList,
			'beginningDate' => $beginningDate, 'beginningTimetableLine' => $beginningTimetableLine, 'endPeriods' => $endPeriods, 'firstDateNumber' => $firstDateNumber, 
			'previousFirstDateNumber' => $previousFirstDateNumber, 'nextFirstDateNumber' => $nextFirstDateNumber, 'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList, 'noteID' => $noteID));
    }

    // Mise a jour de la liste des utilisateurs (en création de réservation)
    /**
     * @Route("/bookingmany/userscreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{labelIDList}/{noteID}/{userFileIDInitialList}/{userFileIDList}",
     * defaults={"userFileIDList" = null},
     * name="booking_many_users_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function many_users_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList)
	{
	return BookingController::users_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList, 1);
	}

    // Mise a jour de la liste des utilisateurs (en création de réservation)
    /**
     * @Route("/bookingone/userscreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{labelIDList}/{noteID}/{userFileIDInitialList}/{userFileIDList}",
     * defaults={"userFileIDList" = null},
     * name="booking_one_users_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function one_users_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList)
    {
	return BookingController::users_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList, 0);
    }

    // Mise a jour de la liste des utilisateurs (en création de réservation)
    public function users_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	
	$selectedUserFiles = BookingApi::getSelectedUserFiles($em, $userFileIDList);
	
	$availableUserFiles = BookingApi::initAvailableUserFiles($em, $userContext->getCurrentFile(), $userFileIDList);

	return $this->render('booking/users.create.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource, 'date' => $date,
		'timetableLinesList' => $timetableLinesList, 'labelIDList' => $labelIDList, 'noteID' => $noteID, 'selectedUserFiles' => $selectedUserFiles, 'availableUserFiles' => $availableUserFiles,
		'userFileIDList' => $userFileIDList, 'userFileIDInitialList' => $userFileIDInitialList));
    }

    // Mise a jour de la liste des utilisateurs (en création de réservation)
    /**
     * @Route("/bookingmany/labelscreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{noteID}/{labelIDInitialList}/{labelIDList}", name="booking_many_labels_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function many_labels_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList)
	{
	return BookingController::labels_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList, 1);
	}

    // Mise a jour de la liste des utilisateurs (en création de réservation)
    /**
     * @Route("/bookingone/labelscreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{noteID}/{labelIDInitialList}/{labelIDList}", name="booking_one_labels_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function one_labels_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList)
    {
	return BookingController::labels_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList, 0);
    }

    // Mise a jour de la liste des étiquettes (en création de réservation)
    public function labels_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$selectedLabels = BookingApi::getSelectedLabels($em, $labelIDList);
	$availableLabels = BookingApi::initAvailableLabels($em, $userContext->getCurrentFile(), $labelIDList);

	return $this->render('booking/labels.create.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource, 'date' => $date,
			'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'noteID' => $noteID, 'selectedLabels' => $selectedLabels, 
			'availableLabels' => $availableLabels, 'labelIDList' => $labelIDList, 'labelIDInitialList' => $labelIDInitialList));
    }

	// Mise a jour de la note (en création de réservation)
    /**
     * @Route("/bookingmany/notecreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_note_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function many_note_create(Request $request, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::note_create($request, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 1);
    }

	// Mise a jour de la note (en création de réservation)
    /**
     * @Route("/bookingone/notecreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_note_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
	public function one_note_create(Request $request, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::note_create($request, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 0);
    }

	// Mise a jour de la note (en création de réservation)
    public function note_create(Request $request, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	$nRepository = $em->getRepository(Note::Class);
	$note = new Note($connectedUser);
	if ($noteID > 0) {
		$note = $nRepository->find($noteID);
	}
	$form = $this->createForm(NoteType::class, $note);
	if ($request->isMethod('POST')) {
		$form->submit($request->request->get($form->getName()));
		if ($form->isSubmitted() && $form->isValid()) {
			if ($noteID <= 0) { // On ne persiste pas en mise à jour.
				$em->persist($note);
			}
			$em->flush();
			return $this->redirectToRoute('booking_'.($many ? 'many' : 'one').'_create',
				array('planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'resourceID' => $resource->getID(),
					'date' => $date->format('Ymd'), 'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList,
					'noteID' => $note->getID()));
		}
    }

	return $this->render('booking/note.create.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod,
			'resource' => $resource, 'date' => $date, 'timetableLinesList' => $timetableLinesList,
			'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList, 'noteID' => $noteID, 'form' => $form->createView()));
	}

	// Suppression de la note (en création de réservation)
    /**
     * @Route("/bookingmany/notedeletecreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_note_delete_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
	 * @ParamConverter("note", options={"mapping": {"noteID": "id"}})
     */
	public function many_note_delete_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, Note $note)
    {
	return BookingController::note_delete_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $note, 1);
    }

	// Suppression de la note (en création de réservation)
    /**
     * @Route("/bookingone/notedeletecreate/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_note_delete_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
	 * @ParamConverter("note", options={"mapping": {"noteID": "id"}})
     */
	public function one_note_delete_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, Note $note)
    {
	return BookingController::note_delete_create($planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $note, 0);
    }

	// Suppression de la note (en création de réservation)
    public function note_delete_create(Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, Note $note, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	// Inutile de persister ici, Doctrine connait déjà la note
	$em->remove($note);
	$em->flush();

	return $this->redirectToRoute('booking_'.($many ? 'many' : 'one').'_create',
		array('planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'resourceID' => $resource->getID(),
		'date' => $date->format('Ymd'), 'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList, 'noteID' => 0));
	}

    // Validation de la création d'une réservation
    /**
     * @Route("/bookingmany/validatecreate/{planificationID}/{planificationPeriodID}/{resourceID}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_validate_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
     */
    public function many_validate_create(Request $request, LoggerInterface $logger, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::validate_create($request, $logger, $planification, $planificationPeriod, $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 1);
    }

    // Validation de la création d'une réservation
    /**
     * @Route("/bookingone/validatecreate/{planificationID}/{planificationPeriodID}/{resourceID}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_validate_create")
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
	 * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
	 * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
     */
    public function one_validate_create(Request $request, LoggerInterface $logger, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::validate_create($request, $logger, $planification, $planificationPeriod, $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 0);
    }

	// Validation de la création d'une réservation
    public function validate_create(Request $request, LoggerInterface $logger, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, $many)
    {
	$logger->info('DBG 1');
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$plRepository = $em->getRepository(PlanificationLine::Class);
    $tRepository = $em->getRepository(Timetable::Class);
    $tlRepository = $em->getRepository(TimetableLine::Class);
    $ufRepository = $em->getRepository(UserFile::Class);
    $lRepository = $em->getRepository(Label::Class);
	$nRepository = $em->getRepository(Note::Class);

	$booking = new Booking($connectedUser, $userContext->getCurrentFile(), $planification, $resource);
	$urlArray  = explode("-", $timetableLinesList);
	list($beginningDateString, $beginningTimetableID, $beginningTimetableLinesList) = explode("+", $urlArray[0]);
	$beginningTimetableLines = explode("*", $beginningTimetableLinesList);
	$beginningTimetableLineID = $beginningTimetableLines[0];
	$beginningTimetableLine = $tlRepository->find($beginningTimetableLineID);
	$booking->setBeginningDate(date_create_from_format('YmdHi', $beginningDateString.$beginningTimetableLine->getBeginningTime()->format('Hi')));
	list($endDateString, $endTimetableID, $endTimetableLinesList) = explode("+", $urlArray[count($urlArray)-1]);
	$endTimetableLines = explode("*", $endTimetableLinesList);
	$endTimetableLineID = $endTimetableLines[count($endTimetableLines)-1];
	$endTimetableLine = $tlRepository->find($endTimetableLineID);
	$booking->setEndDate(date_create_from_format('YmdHi', $endDateString.$endTimetableLine->getEndTime()->format('Hi')));
	$note = new Note($connectedUser);
	if ($noteID > 0) {
		$note = $nRepository->find($noteID);
		$booking->setNote($note->getNote());
		$booking->setFormNote($note);
	}
	$em->persist($booking);

	// Lignes de réservation
	$timetableLines = BookingApi::getTimetableLines($timetableLinesList);
	foreach ($timetableLines as $timetableLineString) {
		list($dateString, $timetableID, $timetableLineID) = explode("-", $timetableLineString);
		
		$date = date_create_from_format('Ymd', $dateString);
		
		$bookingLine = new BookingLine($connectedUser, $booking, $resource);
		$bookingLine->setDate($date);
		$bookingLine->setPlanification($planification);
		$bookingLine->setPlanificationPeriod($planificationPeriod);
		$bookingLine->setPlanificationLine($plRepository->findOneBy(array('planificationPeriod' => $planificationPeriod, 'weekDay' => strtoupper($date->format('D')))));
		$bookingLine->setTimetable($tRepository->find($timetableID));
		$bookingLine->setTimetableLine($tlRepository->find($timetableLineID));
		$em->persist($bookingLine);
	}

	// Utilisateurs de réservation
	$order = 0;
	$userFileIDArray = explode("-", $userFileIDList);
	foreach ($userFileIDArray as $userFileID) {
		$bookingUser = new BookingUser($connectedUser, $booking, $ufRepository->find($userFileID));
		$bookingUser->setOrder(++$order);
		$em->persist($bookingUser);
	}

	// Etiquettes de réservation
	$labelIDArray = array();
	if ($labelIDList != '0') {
		$labelIDArray = explode("-", $labelIDList);
	}
	$order = 0;
	foreach ($labelIDArray as $labelID) {
		$logger->info('DBG 4 _'.$labelID.'_');
		$bookingLabel = new BookingLabel($connectedUser, $booking, $lRepository->find($labelID));
		$bookingLabel->setOrder(++$order);
		$em->persist($bookingLabel);
	}
	$em->flush();
	$request->getSession()->getFlashBag()->add('notice', 'booking.created.ok');

	return $this->redirectToRoute('planning_'.($many ? 'many' : 'one').'_pp',
		array('planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'date' => $beginningDateString));
    }

	// MISE A JOUR DES RESERVATIONS
    // Initialisation de la mise à jour de réservation
    /**
     * @Route("/bookingmany/initupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}", name="booking_many_init_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_init_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date)
    {
	return BookingController::init_update($booking, $planification, $planificationPeriod, $resource, $date, 1);
    }

    // Initialisation de la mise à jour de réservation
    /**
     * @Route("/bookingone/initupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}", name="booking_one_init_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_init_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date)
    {
	return BookingController::init_update($booking, $planification, $planificationPeriod, $resource, $date, 0);
    }

    // Initialisation de la mise à jour de réservation
    public function init_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $many)
    {
    $em = $this->getDoctrine()->getManager();
	$timetableLinesList = BookingApi::getBookingLinesUrl($em, $booking);
	$userFileIDList = BookingApi::getBookingUsersUrl($em, $booking);
	$labelIDList = BookingApi::getBookingLabelsUrl($em, $booking);
	// Note
	$noteID = 0;
	if ($booking->getFormNote() !== null) {
		$noteID = $booking->getFormNote()->getID();
	}

	return $this->redirectToRoute('booking_'.($many ? 'many' : 'one').'_update',
		array('bookingID' => $booking->getID(), 'planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(),
			'resourceID' => $resource->getID(), 'date' => $date->format('Ymd'), 'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList,
			'labelIDList' => $labelIDList, 'noteID' => $noteID));
    }

    // Mise à jour de réservation
    /**
     * @Route("/bookingmany/update/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_update(LoggerInterface $logger, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	$logger->info('BookingController.many_update DBG 1');
	$logger->info('BookingController.many_update DBG 2 _'.$booking->getID().'_');

	return BookingController::update($logger, $booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 1);
    }

    // Mise à jour de réservation
    /**
     * @Route("/bookingone/update/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_update(LoggerInterface $logger, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::update($logger, $booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 0);
    }

    // Mise à jour de réservation
    public function update(LoggerInterface $logger, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, $many)
    {
    $connectedUser = $this->getUser();
    $em = $this->getDoctrine()->getManager();
    $userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	$nRepository = $em->getRepository(Note::Class);
	BookingApi::getBookingLinesUrlBeginningAndEndPeriod($em, $timetableLinesList, $beginningDate, $beginningTimetableLine, $endDate, $endTimetableLine);
	// Utilisateurs
	$userFiles = BookingApi::getUserFiles($em, $userFileIDList);
	// Etiquettes
	$labels = BookingApi::getLabels($em, $labelIDList);
	// Note
	$note = new Note($connectedUser);
	if ($noteID > 0) {
		$note = $nRepository->find($noteID);
	}

	return $this->render('booking/update.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'booking' => $booking, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource,
			'date' => $date, 'timetableLinesList' => $timetableLinesList, 'beginningDate' => $beginningDate, 'beginningTimetableLine' => $beginningTimetableLine,
			'endDate' => $endDate, 'endTimetableLine' => $endTimetableLine, 'userFiles' => $userFiles, 'userFileIDList' => $userFileIDList,
			'labels' => $labels, 'labelIDList' => $labelIDList, 'noteID' => $noteID, 'note' => $note));
    }

    // Mise a jour de la periode de fin (en mise à jour de réservation)
    /**
     * @Route("/bookingmany/endperiodupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{firstDateNumber}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_end_period_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_end_period_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::end_period_update($booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID, 1);
    }

    // Mise a jour de la periode de fin (en mise à jour de réservation)
    /**
     * @Route("/bookingone/endperiodupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{firstDateNumber}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_end_period_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_end_period_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::end_period_update($booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID, 0);
    }

    // Mise a jour de la periode de fin (en mise à jour de réservation)
    public function end_period_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $firstDateNumber, $userFileIDList, $labelIDList, $noteID, $many)
    {
    $connectedUser = $this->getUser();
    $em = $this->getDoctrine()->getManager();
    $userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$cellArray  = explode("-", $timetableLinesList);
    $ttlRepository = $em->getRepository(TimetableLine::Class);
	list($beginningDateString, $beginningTimetableID, $beginningTimetableLineID) = explode("+", $cellArray[0]);
	$beginningDate = date_create_from_format("Ymd", $beginningDateString);
	$beginningTimetableLine = $ttlRepository->find($beginningTimetableLineID);
    $endPeriods = BookingApi::getEndPeriods($em, $planificationPeriod, $resource, $beginningDate, $beginningTimetableLine, $booking->getID(), $firstDateNumber, $nextFirstDateNumber);
	// Calucl du premier jour affiché précedent
	$previousFirstDateNumber = ($firstDateNumber < Constants::MAXIMUM_NUMBER_BOOKING_DATES_DISPLAYED) ? 0 : ($firstDateNumber - Constants::MAXIMUM_NUMBER_BOOKING_DATES_DISPLAYED);

	return $this->render('booking/period.end.update.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'booking' => $booking, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod,
			'resource' => $resource, 'date' => $date, 'timetableLinesList' => $timetableLinesList, 'beginningDate' => $beginningDate,
			'beginningTimetableLine' => $beginningTimetableLine, 'endPeriods' => $endPeriods, 'firstDateNumber' => $firstDateNumber,
			'previousFirstDateNumber' => $previousFirstDateNumber, 'nextFirstDateNumber' => $nextFirstDateNumber, 'userFileIDList' => $userFileIDList,
			'labelIDList' => $labelIDList, 'noteID' => $noteID));
    }

    // Mise a jour de la liste des utilisateurs (en mise a jour de réservation)
    /**
     * @Route("/bookingmany/usersupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{labelIDList}/{noteID}/{userFileIDInitialList}/{userFileIDList}",
     * defaults={"userFileIDList" = null},
     * name="booking_many_users_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_users_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList)
    {
	return BookingController::users_update($booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList, 1);
    }

    // Mise a jour de la liste des utilisateurs (en mise a jour de réservation)
    /**
     * @Route("/bookingone/usersupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{labelIDList}/{noteID}/{userFileIDInitialList}/{userFileIDList}",
     * defaults={"userFileIDList" = null},
     * name="booking_one_users_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_users_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList)
    {
	return BookingController::users_update($booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList, 0);
    }

    // Mise a jour de la liste des utilisateurs (en mise à jour de réservation)
    public function users_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $labelIDList, $noteID, $userFileIDInitialList, $userFileIDList, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	
	$selectedUserFiles = BookingApi::getSelectedUserFiles($em, $userFileIDList);
	$availableUserFiles = BookingApi::initAvailableUserFiles($em, $userContext->getCurrentFile(), $userFileIDList);

	return $this->render('booking/users.update.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'booking' => $booking, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource,
			'date' => $date, 'timetableLinesList' => $timetableLinesList, 'labelIDList' => $labelIDList, 'noteID' => $noteID,
			'selectedUserFiles' => $selectedUserFiles, 'availableUserFiles' => $availableUserFiles, 'userFileIDList' => $userFileIDList, 'userFileIDInitialList' => $userFileIDInitialList));
    }

	// Mise a jour de la liste des étiquettes (en mise a jour de réservation)
    /**
     * @Route("/bookingmany/labelsupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{noteID}/{labelIDInitialList}/{labelIDList}", name="booking_many_labels_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_labels_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList)
    {
	return BookingController::labels_update($booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList, 1);
    }

	// Mise a jour de la liste des étiquettes (en mise a jour de réservation)
    /**
     * @Route("/bookingone/labelsupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{noteID}/{labelIDInitialList}/{labelIDList}", name="booking_one_labels_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_labels_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList)
    {
	return BookingController::labels_update($booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList, 0);
    }

    // Mise a jour de la liste des étiquettes (en mise à jour de réservation)
    public function labels_update(Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $noteID, $labelIDInitialList, $labelIDList, $many)
	{
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	
	$selectedLabels = BookingApi::getSelectedLabels($em, $labelIDList);
	$availableLabels = BookingApi::initAvailableLabels($em, $userContext->getCurrentFile(), $labelIDList);
	
	return $this->render('booking/labels.update.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'booking' => $booking, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource,
			'date' => $date, 'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'noteID' => $noteID,
			'selectedLabels' => $selectedLabels, 'availableLabels' => $availableLabels, 'labelIDList' => $labelIDList, 'labelIDInitialList' => $labelIDInitialList));
    }

	// Mise a jour de la note (en mise à jour de réservation)
    /**
     * @Route("/bookingmany/noteupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_note_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_note_update(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::note_update($request, $booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 1);
    }

	// Mise a jour de la note (en mise à jour de réservation)
    /**
     * @Route("/bookingone/noteupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_note_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_note_update(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
    {
	return BookingController::note_update($request, $booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 0);
    }

	// Mise a jour de la note (en mise à jour de réservation)
    public function note_update(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, $many)
    {
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	$nRepository = $em->getRepository(Note::Class);
	$note = new Note($connectedUser);
	if ($noteID > 0) {
		$note = $nRepository->find($noteID);
	}
	$form = $this->createForm(NoteType::class, $note);

	if ($request->isMethod('POST')) {
		$form->submit($request->request->get($form->getName()));
		if ($form->isSubmitted() && $form->isValid()) {
			if ($noteID <= 0) { // On ne persiste pas en mise à jour.
				$em->persist($note);
			}
			$em->flush();
			return $this->redirectToRoute('booking_'.($many ? 'many' : 'one').'_update',
				array('bookingID' => $booking->getID(), 'planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'resourceID' => $resource->getID(),
					'date' => $date->format('Ymd'), 'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList, 'noteID' => $note->getID()));
		}
    }

	return $this->render('booking/note.update.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'booking' => $booking, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource, 'date' => $date,
			'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList, 'noteID' => $noteID, 'form' => $form->createView()));
    }

	// Suppression de la note (en mise à jour de réservation)
    /**
     * @Route("/bookingmany/notedeleteupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_note_delete_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
	 * @ParamConverter("note", options={"mapping": {"noteID": "id"}})
     */
    public function many_note_delete_update(LoggerInterface $logger, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, Note $note)
    {
	return BookingController::note_delete_update($logger, $booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $note, 1);
    }

	// Suppression de la note (en mise à jour de réservation)
    /**
     * @Route("/bookingone/notedeleteupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_note_delete_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
	 * @ParamConverter("note", options={"mapping": {"noteID": "id"}})
     */
    public function one_note_delete_update(LoggerInterface $logger, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, Note $note)
    {
	return BookingController::note_delete_update($logger, $booking, $planification, $planificationPeriod, $resource, $date, $timetableLinesList, $userFileIDList, $labelIDList, $note, 0);
    }

	// Suppression de la note (en mise à jour de réservation)
    public function note_delete_update(LoggerInterface $logger, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $timetableLinesList, $userFileIDList, $labelIDList, Note $note, $many)
    {
	$logger->info('BookingController.note_delete_update DBG 1');
	$logger->info('BookingController.note_delete_update DBG 2 _'.$booking->getID().'_');
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur
	// Inutile de persister ici, Doctrine connait déjà la note
	$booking->setNullFormNote();
	$em->remove($note);
	$em->flush();
	return $this->redirectToRoute('booking_'.($many ? 'many' : 'one').'_update',
		array('bookingID' => $booking->getID(), 'planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'resourceID' => $resource->getID(),
			'date' => $date->format('Ymd'), 'timetableLinesList' => $timetableLinesList, 'userFileIDList' => $userFileIDList, 'labelIDList' => $labelIDList, 'noteID' => 0));
    }

	// Validation de la mise à jour d'une réservation
    /**
     * @Route("/bookingmany/validateupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_many_validate_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
     */
	public function many_validate_update(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
	{
	return BookingController::validate_update($request, $booking, $planification, $planificationPeriod, $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 1);
	}

	// Validation de la mise à jour d'une réservation
    /**
     * @Route("/bookingone/validateupdate/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{timetableLinesList}/{userFileIDList}/{labelIDList}/{noteID}", name="booking_one_validate_update")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
     */
	public function one_validate_update(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID)
	{
	return BookingController::validate_update($request, $booking, $planification, $planificationPeriod, $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, 0);
	}

	// Validation de la mise à jour d'une réservation
    public function validate_update(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, $timetableLinesList, $userFileIDList, $labelIDList, $noteID, $many)
    {
	$connectedUser = $this->getUser();
	$em = $this->getDoctrine()->getManager();
	$userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$plRepository = $em->getRepository(PlanificationLine::Class);
    $tRepository = $em->getRepository(Timetable::Class);
    $tlRepository = $em->getRepository(TimetableLine::Class);
    $ufRepository = $em->getRepository(UserFile::Class);
    $bliRepository = $em->getRepository(BookingLine::Class);
    $buRepository = $em->getRepository(BookingUser::Class);
    $blaRepository = $em->getRepository(BookingLabel::Class);
    $lRepository = $em->getRepository(Label::Class);
    $nRepository = $em->getRepository(Note::Class);
	
	$urlArray  = explode("-", $timetableLinesList);
	
	// Mise à jour des dates heures mini et maxi de la réservation.
	list($beginningDateString, $beginningTimetableID, $beginningTimetableLinesList) = explode("+", $urlArray[0]);
	$beginningTimetableLines = explode("*", $beginningTimetableLinesList);
	$beginningTimetableLineID = $beginningTimetableLines[0];
	$beginningTimetableLine = $tlRepository->find($beginningTimetableLineID);
	$booking->setBeginningDate(date_create_from_format('YmdHi', $beginningDateString.$beginningTimetableLine->getBeginningTime()->format('Hi')));
	list($endDateString, $endTimetableID, $endTimetableLinesList) = explode("+", $urlArray[count($urlArray)-1]);
	$endTimetableLines = explode("*", $endTimetableLinesList);
	$endTimetableLineID = $endTimetableLines[count($endTimetableLines)-1];
	$endTimetableLine = $tlRepository->find($endTimetableLineID);
	$booking->setEndDate(date_create_from_format('YmdHi', $endDateString.$endTimetableLine->getEndTime()->format('Hi')));
	// Mise à jour de la note
	$note = new Note($connectedUser);
	if ($noteID > 0) {
		$note = $nRepository->find($noteID);
		$booking->setNote($note->getNote());
		$booking->setFormNote($note);
	} else {
		$booking->setNote(null);
		$booking->setNullFormNote();
	}
	$em->persist($booking);
	$url_timetableLinesString = BookingApi::getTimetableLines($timetableLinesList);
	
	// Parcours des lignes de réservation dans l'ordre chronologique.
	$bookingLines = $bliRepository->findBy(array('booking' => $booking), array('ddate' => 'asc', 'timetable' => 'asc', 'timetableLine' => 'asc')); // TPRR. Voir le champ date (date ou ddate)
	
	foreach ($bookingLines as $bookingLine) {
		$booking_timetableLineString = $bookingLine->getDate()->format('Ymd').'-'.$bookingLine->getTimetable()->getID().'-'.$bookingLine->getTimetableLine()->getID();
		
		if (!in_array($booking_timetableLineString, $url_timetableLinesString)) { // La ligne de réservation n'appartient pas aux lignes de l'Url. Elle est supprimée.
			$em->remove($bookingLine);
		}
	}
	// Parcours des lignes de réservation de l'Url.
	foreach ($url_timetableLinesString as $url_timetableLineString) {
		list($dateString, $timetableID, $timetableLineID) = explode("-", $url_timetableLineString);
		$date = date_create_from_format('Ymd', $dateString);
		// Recherche de la ligne de réservation en base.
		$bookingLineDB = $bliRepository->findOneBy(array('resource' => $resource, 'ddate' => $date, 'timetable' => $tRepository->find($timetableID), 'timetableLine' => $tlRepository->find($timetableLineID)));
		if ($bookingLineDB === null) { // La ligne de réservation n'existe pas en base, on la crée.
			$bookingLine = new BookingLine($connectedUser, $booking, $resource);
			$bookingLine->setDate($date);
			$bookingLine->setPlanification($planification);
			$bookingLine->setPlanificationPeriod($planificationPeriod);
			$bookingLine->setPlanificationLine($plRepository->findOneBy(array('planificationPeriod' => $planificationPeriod, 'weekDay' => strtoupper($date->format('D')))));
			$bookingLine->setTimetable($tRepository->find($timetableID));
			$bookingLine->setTimetableLine($tlRepository->find($timetableLineID));
			$em->persist($bookingLine);
		}
	}
	
	// Tableau des utilisateurs de l'Url
	$url_userFileID = explode("-", $userFileIDList);
	// Parcours des utilisateurs de la réservation.
	$bookingUsers = $buRepository->findBy(array('booking' => $booking), array('id' => 'asc'));
	
	foreach ($bookingUsers as $bookingUser) {
		if (!in_array($bookingUser->getUserFile()->getID(), $url_userFileID)) { // L'utilisateur n'appartient pas a la liste de l'Url. Il est supprimé.
			$em->remove($bookingUser);
		}
	}
	$order = 0;
	// Parcours des utilisateurs de l'Url.
	foreach ($url_userFileID as $userFileID) {
		$bookingUser = $buRepository->findOneBy(array('booking' => $booking, 'userFile' => $ufRepository->find($userFileID)));
		if ($bookingUser === null) { // L'utilisateur n'est pas rattaché en base à la réservation --> on crée le lien.
			$bookingUser = new BookingUser($connectedUser, $booking, $ufRepository->find($userFileID));
		}
		$bookingUser->setOrder(++$order); // Pour tous les utilisateurs de l'Url, on met à jour le numéro d'ordre
		$em->persist($bookingUser);
	}
	// Tableau des étiquettes de l'Url
	$url_labelID = array();
	if ($labelIDList != '0') { // La chaine '0' équivaut à une chaine vide
		$url_labelID = explode("-", $labelIDList);
	}
	// Parcours des étiquettes de la réservation.
	$bookingLabels = $blaRepository->findBy(array('booking' => $booking), array('id' => 'asc'));
	
	foreach ($bookingLabels as $bookingLabel) {
		if (!in_array($bookingLabel->getLabel()->getID(), $url_labelID)) { // L'étiquette n'appartient pas a la liste de l'Url. Elle est supprimée.
			$em->remove($bookingLabel);
		}
	}
	$order = 0;
	// Parcours des étiquettes de l'Url.
	foreach ($url_labelID as $labelID) {
		$bookingLabel = $blaRepository->findOneBy(array('booking' => $booking, 'label' => $lRepository->find($labelID)));
		if ($bookingLabel === null) { // L'étiquette n'est pas rattachée en base à la réservation --> on crée le lien.
			$bookingLabel = new BookingLabel($connectedUser, $booking, $lRepository->find($labelID));
		}
		$bookingLabel->setOrder(++$order); // Pour toutes les étiquettes de l'Url, on met à jour le numéro d'ordre
		$em->persist($bookingLabel);
	}
	$em->flush();
	$request->getSession()->getFlashBag()->add('notice', 'booking.updated.ok');
	return $this->redirectToRoute('planning_'.($many ? 'many' : 'one').'_pp',
		array('planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'date' => $beginningDateString));
    }

    // Suppression d'une réservation
    /**
     * @Route("/bookingmany/delete/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}", name="booking_many_delete")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function many_delete(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date)
    {
	return BookingController::delete($request, $booking, $planification, $planificationPeriod, $resource, $date, 1);
    }

    // Suppression d'une réservation
    /**
     * @Route("/bookingone/delete/{bookingID}/{planificationID}/{planificationPeriodID}/{resourceID}/{date}", name="booking_one_delete")
	 * @ParamConverter("booking", options={"mapping": {"bookingID": "id"}})
	 * @ParamConverter("planification", options={"mapping": {"planificationID": "id"}})
     * @ParamConverter("planificationPeriod", options={"mapping": {"planificationPeriodID": "id"}})
     * @ParamConverter("resource", options={"mapping": {"resourceID": "id"}})
	 * @ParamConverter("date", options={"format": "Ymd"})
     */
    public function one_delete(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date)
    {
	return BookingController::delete($request, $booking, $planification, $planificationPeriod, $resource, $date, 0);
    }

    // Suppression d'une réservation
    public function delete(Request $request, Booking $booking, Planification $planification, PlanificationPeriod $planificationPeriod, Resource $resource, \Datetime $date, $many)
    {
    $connectedUser = $this->getUser();
    $em = $this->getDoctrine()->getManager();
    $userContext = new UserContext($em, $connectedUser); // contexte utilisateur

	$timetableLinesList = BookingApi::getBookingLinesUrl($em, $booking);
	BookingApi::getBookingLinesUrlBeginningAndEndPeriod($em, $timetableLinesList, $beginningDate, $beginningTimetableLine, $endDate, $endTimetableLine);
	$userFileIDList = BookingApi::getBookingUsersUrl($em, $booking);
	// Utilisateurs
	$userFileIDArray = explode("-", $userFileIDList);
	$userFiles = array();
	$ufRepository = $em->getRepository(UserFile::Class);
	foreach ($userFileIDArray as $userFileID) {
		$userFile = $ufRepository->find($userFileID);
		if ($userFile !== null) {
			$userFiles[] = $userFile;
		}
	}
	// Libellés
	$labelIDList = BookingApi::getBookingLabelsUrl($em, $booking);
	$labelIDArray = explode("-", $labelIDList);
	$labels = array();
	$lRepository = $em->getRepository(Label::Class);
	foreach ($labelIDArray as $labelID) {
		$label = $lRepository->find($labelID);
		if ($label !== null) {
			$labels[] = $label;
		}
	}
	// Note
	$noteID = 0;
	$note = new Note($connectedUser);
	$bRepository = $em->getRepository(Note::Class);
	if ($booking->getFormNote() !== null) {
		$noteID = $booking->getFormNote()->getID();
		$note = $nRepository->find($noteID);
	}

    $form = $this->get('form.factory')->create();
 
	if ($request->isMethod('POST')) {
		$form->submit($request->request->get($form->getName()));
		if ($form->isSubmitted() && $form->isValid()) {
			// Inutile de persister ici, Doctrine connait déjà la reservation
			$em->remove($booking);
			$em->flush();
			$request->getSession()->getFlashBag()->add('notice', 'booking.deleted.ok');
			return $this->redirectToRoute('planning_'.($many ? 'many' : 'one').'_pp',
				array('planificationID' => $planification->getID(), 'planificationPeriodID' => $planificationPeriod->getID(), 'date' => $date->format('Ymd')));
		}
    }

	return $this->render('booking/delete.'.($many ? 'many' : 'one').'.html.twig',
		array('userContext' => $userContext, 'booking' => $booking, 'planification' => $planification, 'planificationPeriod' => $planificationPeriod, 'resource' => $resource,
			'date' => $date, 'timetableLinesList' => $timetableLinesList, 'beginningDate' => $beginningDate, 'beginningTimetableLine' => $beginningTimetableLine,
			'endDate' => $endDate, 'endTimetableLine' => $endTimetableLine, 'userFiles' => $userFiles, 'userFileIDList' => $userFileIDList,
			'labels' => $labels, 'labelIDList' => $labelIDList, 'noteID' => $noteID, 'note' => $note, 'form' => $form->createView()));
    }
}
