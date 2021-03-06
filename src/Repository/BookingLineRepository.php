<?php

namespace App\Repository;

use App\Entity\BookingLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

/**
 * @method BookingLine|null find($id, $lockMode = null, $lockVersion = null)
 * @method BookingLine|null findOneBy(array $criteria, array $orderBy = null)
 * @method BookingLine[]    findAll()
 * @method BookingLine[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingLineRepository extends ServiceEntityRepository
{
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct($registry, BookingLine::class);
    }

	public function getBookingLines(\App\Entity\Booking $booking)
	{
	$qb = $this->createQueryBuilder('bl');
	$qb->select('bl.ddate date');
	$qb->addSelect('p.id planificationID');
	$qb->addSelect('pp.id planificationPeriodID');
	$qb->addSelect('pl.id planificationLineID');
	$qb->addSelect('t.id timetableID');
	$qb->addSelect('tl.id timetableLineID');
	$qb->where('bl.booking = :booking')->setParameter('booking', $booking);
	$qb->innerJoin('bl.planification', 'p');
	$qb->innerJoin('bl.planificationPeriod', 'pp');
	$qb->innerJoin('bl.planificationLine', 'pl');
	$qb->innerJoin('bl.timetable', 't');
	$qb->innerJoin('bl.timetableLine', 'tl');
	$qb->orderBy('bl.ddate', 'ASC');
	$qb->addOrderBy('p.id', 'ASC');
	$qb->addOrderBy('pp.id', 'ASC');
	$qb->addOrderBy('pl.id', 'ASC');
	$qb->addOrderBy('t.id', 'ASC');
	$qb->addOrderBy('tl.id', 'ASC');
	$query = $qb->getQuery();
	$results = $query->getResult();
	return $results;
	}
}
