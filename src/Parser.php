<?php
/**
 * This file is part of the lenex-php package.
 *
 * The Lenex file format is created by Swimrankings.net
 *
 * (c) Leon Verschuren <lverschuren@hotmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace leonverschuren\Lenex;

use Closure;
use DateTime;
use leonverschuren\Lenex\Model\AgeDate;
use leonverschuren\Lenex\Model\AgeGroup;
use leonverschuren\Lenex\Model\Athlete;
use leonverschuren\Lenex\Model\Club;
use leonverschuren\Lenex\Model\Constructor;
use leonverschuren\Lenex\Model\Contact;
use leonverschuren\Lenex\Model\Entry;
use leonverschuren\Lenex\Model\Event;
use leonverschuren\Lenex\Model\Fee;
use leonverschuren\Lenex\Model\Handicap;
use leonverschuren\Lenex\Model\Heat;
use leonverschuren\Lenex\Model\Judge;
use leonverschuren\Lenex\Model\Lenex;
use leonverschuren\Lenex\Model\Meet;
use leonverschuren\Lenex\Model\MeetInfo;
use leonverschuren\Lenex\Model\Official;
use leonverschuren\Lenex\Model\PointTable;
use leonverschuren\Lenex\Model\Pool;
use leonverschuren\Lenex\Model\Qualify;
use leonverschuren\Lenex\Model\Ranking;
use leonverschuren\Lenex\Model\Relay;
use leonverschuren\Lenex\Model\RelayPosition;
use leonverschuren\Lenex\Model\Result;
use leonverschuren\Lenex\Model\Session;
use leonverschuren\Lenex\Model\Split;
use leonverschuren\Lenex\Model\SwimStyle;
use leonverschuren\Lenex\Model\TimeStandardRef;
use SimpleXMLElement;

class Parser
{
    /**
     * stores objects corresponding to their
     * id
     * @type Array
     */
    private $objectNameIdTable = Array();
    
    private $objectInjectionTable = Array();
    
    /**
     * stores an object for resolving
     * @param string $objectName
     * @param object $objectInstance
     * @param int $objectId
     */
    public function registerObjectById($objectName, &$objectInstance, $objectId)
    {
	$this->objectNameIdTable[$objectName][$objectId] = $objectInstance;
    }
    
    public function registerObjectForInjection(&$objectInstance, $foreignObjectName)
    {
	$this->objectInjectionTable[$foreignObjectName][] = $objectInstance;
    }
    
    /**
     * @param SimpleXMLElement $document
     * @return Lenex
     */
    public function parseResult(SimpleXMLElement $document)
    {
        $result = $this->extractLenex($document);
	$this->resolveInjections();
	return $result;
    }
    
    public function resolveInjections()
    {
	foreach($this->objectInjectionTable as $objectName => $objectList)
	{
	    foreach($objectList as $index => $objectInstance)
	    {
		$id = $objectInstance->{$objectName."Id"};
		
		if(isset($this->objectNameIdTable[$objectName][$id])){
		    $objectInstance->{$objectName} = 
			$this->objectNameIdTable[$objectName][$id];
		}
	    }
	}
    }


    public function extractLenex(SimpleXMLElement $document)
    {
        $object = new Lenex();

        $fields = [
            'CONSTRUCTOR'      => function (Lenex $object, $value) {
                $object->constructor = ($this->extractConstructor($value));
            },
            'MEETS'            => function (Lenex $object, $value) {
                $object->meets = ($this->extractMeets($value));
            },
            'RECORDLISTS'      => function () {
            },
            'TIMESTANDARDLIST' => function () {
            },
            'version'          => 'version',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Constructor
     */
    public function extractConstructor(SimpleXMLElement $document)
    {
        $object = new Constructor();

        $fields = [
            'name'         => 'name',
            'registration' => 'registration',
            'version'      => 'version',
            'CONTACT'      => function (Constructor $object, $value) {
                $object->contact = $this->extractContact($value);
            },
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Contact
     */
    public function extractContact(SimpleXMLElement $document)
    {
        $object = new Contact();

        $fields = [
            'city'     => 'city',
            'country'  => 'country',
            'email'    => 'email',
            'fax'      => 'fax',
            'internet' => 'internet',
            'name'     => 'name',
            'mobile'   => 'mobile',
            'phone'    => 'phone',
            'state'    => 'state',
            'street'   => 'street',
            'street2'  => 'street2',
            'zip'      => 'zip',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Meet[]
     */
    public function extractMeets(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->MEET as $meet) {
            $objects[] = $this->extractMeet($meet);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Meet
     */
    public function extractMeet(SimpleXMLElement $document)
    {
        $object = new Meet();

        $fields = [
            'AGEDATE'        => function (Meet $object, $value) {
                $object->ageDate = $this->extractAgeDate($value);
            },
            'altitude'       => 'altitude',
            'city'           => 'city',
            'city.en'        => 'cityEn',
            'CLUBS'          => function (Meet $object, $value) {
                $object->clubs = $this->extractClubs($value);
		foreach($object->clubs as $club){
		    $club->meet = $object;
		}
            },
            'CONTACT'        => function (Meet $object, $value) {
                $object->contact = $this->extractContact($value);
            },
            'course'         => 'course',
            'deadline'       => function (Meet $object, $value) {
                $object->deadline = new DateTime($value);
            },
            'deadlinetime'   => 'deadlineTime',
            'entrystartdate' => function (Meet $object, $value) {
                $object->entryStartDate = new DateTime($value);
            },
            'entrytype'      => 'entryType',
            'FEES'           => function (Meet $object, $value) {
                $object->fees = $this->extractFees($value);
            },
            'hostclub'       => 'hostClub',
            'hostclub.url'   => 'hostClubUrl',
            'maxentries'     => 'maxEntries',
            'name'           => 'name',
            'name.en'        => 'nameEn',
            'nation'         => 'nation',
            'number'         => 'number',
            'organizer'      => 'organizer',
            'organizer.url'  => 'organizerUrl',
            'POINTTABLE'     => function (Meet $object, $value) {
                $object->pointTable = $this->extractPointTable($value);
            },
            'POOL'           => function (Meet $object, $value) {
                $object->pool = $this->extractPool($value);
            },
            'QUALIFY'        => function (Meet $object, $value) {
                $object->qualify = $this->extractQualify($value);
            },
            'reservecount'   => 'reserveCount',
            'result.url'     => 'resultUrl',
            'SESSIONS'       => function (Meet $object, $value) {
                $object->sessions = $this->extractSessions($value);
            },
            'startmethod'    => 'startMethod',
            'state'          => 'state',
            'swrid'          => 'swrId',
            'timing'         => 'timing',
            'type'           => 'type',
            'withdrawuntil'  => function (Meet $object, $value) {
                $object->withdrawUntil = new DateTime($value);
            },
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return AgeDate
     */
    public function extractAgeDate(SimpleXMLElement $document)
    {
        $object = new AgeDate();

        $fields = [
            'type'  => 'type',
            'value' => function (AgeDate $object, $value) {
                $object->value = new DateTime($value);
            },
        ];

        return $this->transform($document, $fields, $object);
    }

    /**
     * @param SimpleXMLElement $document
     * @return Club[]
     */
    public function extractClubs(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->CLUB as $value) {
            $objects[] = $this->extractClub($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Club
     */
    public function extractClub(SimpleXMLElement $document)
    {
        $object = new Club();

        $fields = [
            'ATHLETES'     => function (Club $object, $value) {
                $object->athletes = $this->extractAthletes($value);
            },
            'code'         => 'code',
            'CONTACT'      => function (Club $object, $value) {
                $object->contact = $this->extractContact($value);
            },
            'name'         => 'name',
            'name.en'      => 'nameEn',
            'nation'       => 'nation',
            'number'       => 'number',
            'OFFICIALS'    => function (Club $object, $value) {
                $object->officials = $this->extractOfficials($value);
            },
            'region'       => 'region',
            'RELAYS'       => function (Club $object, $value) {
                $object->relays = $this->extractRelays($value);
            },
            'shortname'    => 'shortName',
            'shortname.en' => 'shortNameEn',
            'swrid'        => 'swrId',
            'type'         => 'type',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Athlete[]
     */
    public function extractAthletes(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->ATHLETE as $value) {
            $objects[] = $this->extractAthlete($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Athlete
     */
    public function extractAthlete(SimpleXMLElement $document)
    {
        $object = new Athlete();

        $fields = [
            'athleteid'    => 'athleteId',
            'birthdate'    => function (Athlete $object, $value) {
                $object->birthDate = new DateTime($value);
            },
            'CLUB'         => function (Athlete $object, $value) {
                $object->club = $this->extractClub($value);
            },
            'ENTRIES'      => function (Athlete $object, $value) {
                $object->entries = $this->extractEntries($value);
            },
            'firstname'    => 'firstName',
            'firstname.en' => 'firstNameEn',
            'gender'       => 'gender',
            'HANDICAP'     => function (Athlete $object, $value) {
                $object->handicap = $this->extractHandicap($value);
            },
            'lastname'     => 'lastName',
            'lastname.en'  => 'lastNameEn',
            'level'        => 'level',
            'license'      => 'license',
            'nameprefix'   => 'namePrefix',
            'nation'       => 'nation',
            'passport'     => 'passport',
            'RESULTS'      => function (Athlete $object, $value) {
                $object->results = $this->extractResults($value);
            },
            'swrid'        => 'swrId',
        ];
	
        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("athlete", $formattedObject, $formattedObject->athleteId);
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Entry[]
     */
    public function extractEntries(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->ENTRY as $value) {
            $objects[] = $this->extractEntry($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Entry
     */
    public function extractEntry(SimpleXMLElement $document)
    {
        $object = new Entry();

        $fields = [
            'agegroupid'     => 'ageGroupId',
            'entrycourse'    => 'entryCourse',
            'entrytime'      => 'entryTime',
            'eventid'        => 'eventId',
            'heatid'         => 'heatId',
            'lane'           => 'lane',
            'MEETINFO'       => function (Entry $object, $value) {
                $object->meetInfo = $this->extractMeetInfo($value);
            },
            'RELAYPOSITIONS' => function (Entry $object, $value) {
                $object->relayPositions = ($this->extractRelayPositions($value));
            },
            'status'         => 'status',
        ];
	
        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectForInjection($formattedObject, "ageGroup");
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return MeetInfo
     */
    public function extractMeetInfo(SimpleXMLElement $document)
    {
        $object = new MeetInfo();

        $fields = [
            'approved'          => 'approved',
            'city'              => 'city',
            'course'            => 'course',
            'date'              => 'date',
            'daytime'           => 'dayTime',
            'name'              => 'name',
            'nation'            => 'nation',
            'POOL'              => function (MeetInfo $object, $value) {
                $object->pool = ($this->extractPool($value));
            },
            'qualificationtime' => 'qualificationTime',
            'state'             => 'state',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return RelayPosition[]
     */
    public function extractRelayPositions(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->RELAYPOSITION as $value) {
            $objects[] = $this->extractRelayPosition($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return RelayPosition
     */
    public function extractRelayPosition(SimpleXMLElement $document)
    {
        $object = new RelayPosition();

        $fields = [
            'ATHLETE'      => function (RelayPosition $object, $value) {
                $object->athlete = ($this->extractAthlete($value));
            },
            'athleteid'    => 'athleteId',
            'MEETINFO'     => function (RelayPosition $object, $value) {
                $object->meetInfo = ($this->extractMeetInfo($value));
            },
            'number'       => 'number',
            'reactiontime' => 'reactionTime',
            'status'       => 'status',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectForInjection($formattedObject, "athlete");
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Handicap
     */
    public function extractHandicap(SimpleXMLElement $document)
    {
        $object = new Handicap();

        $fields = [
            'breast'    => 'breast',
            'exception' => 'exception',
            'free'      => 'free',
            'medley'    => 'medley',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Result[]
     */
    public function extractResults(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->RESULT as $value) {
            $objects[] = $this->extractResult($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Result
     */
    public function extractResult(SimpleXMLElement $document)
    {
        $object = new Result();

        $fields = [
            'comment'        => 'comment',
            'eventid'        => 'eventId',
            'heatid'         => 'heatId',
            'lane'           => 'lane',
            'points'         => 'points',
            'reactiontime'   => 'reactionTime',
            'RELAYPOSITIONS' => function (Result $object, $value) {
                $object->relayPositions = ($this->extractRelayPositions($value));
            },
            'resultid'       => 'resultId',
            'status'         => 'status',
            'SPLITS'         => function (Result $object, $value) {
                $object->splits = ($this->extractSplits($value));
            },
            'swimtime'       => 'swimTime',
        ];
	
	$formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectForInjection($formattedObject, "event");
	$this->registerObjectForInjection($formattedObject, "heat");
	$this->registerObjectById("result", $formattedObject, $formattedObject->resultId);
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Split[]
     */
    public function extractSplits(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->SPLIT as $value) {
            $objects[] = $this->extractSplit($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Split
     */
    public function extractSplit(SimpleXMLElement $document)
    {
        $object = new Split();

        $fields = [
            'distance' => 'distance',
            'swimtime' => 'swimTime',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Official[]
     */
    public function extractOfficials(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->OFFICIAL as $value) {
            $objects[] = $this->extractOfficial($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Official
     */
    public function extractOfficial(SimpleXMLElement $document)
    {
        $object = new Official();

        $fields = [
            'CONTACT'    => function (Official $object, $value) {
                $object->contact = ($this->extractContact($value));
            },
            'firstname'  => 'firstName',
            'gender'     => 'gender',
            'grade'      => 'grade',
            'lastname'   => 'lastName',
            'license'    => 'license',
            'nameprefix' => 'namePrefix',
            'nation'     => 'nation',
            'officialid' => 'officialId',
            'passport'   => 'passport',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("official", $formattedObject, $formattedObject->officialId);
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Official[]
     */
    public function extractRelays(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->RELAY as $value) {
            $objects[] = $this->extractRelay($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Relay
     */
    public function extractRelay(SimpleXMLElement $document)
    {
        $object = new Relay();

        $fields = [
            'agemax'         => 'ageMax',
            'agemin'         => 'ageMin',
            'agetotalmax'    => 'ageTotalMax',
            'agetotalmin'    => 'ageTotalMin',
            'CLUB'           => function (Relay $object, $value) {
                $object->club = ($this->extractClub($value));
            },
            'ENTRIES'        => function (Relay $object, $value) {
                $object->entries = ($this->extractEntries($value));
            },
            'gender'         => 'gender',
            'handicap'       => 'handicap',
            'name'           => 'name',
            'number'         => 'number',
            'RELAYPOSITIONS' => function (Relay $object, $value) {
                $object->relayPositions = ($this->extractRelayPositions($value));
            },
            'RESULTS'        => function (Relay $object, $value) {
                $object->results = ($this->extractResults($value));
            },
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Fee[]
     */
    public function extractFees(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->FEE as $value) {
            $objects[] = $this->extractFee($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Fee
     */
    public function extractFee(SimpleXMLElement $document)
    {
        $object = new Fee();

        $fields = [
            'currency' => 'currency',
            'type'     => 'type',
            'value'    => 'value',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return PointTable
     */
    public function extractPointTable(SimpleXMLElement $document)
    {
        $object = new PointTable();

        $fields = [
            'name'         => 'name',
            'pointtableid' => 'pointTableId',
            'version'      => 'version',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("pointTable", $formattedObject, $formattedObject->pointTableId);
	return $formattedObject;
    }

    /**
     * @param SimpleXMLElement $document
     * @return Pool
     */
    public function extractPool(SimpleXMLElement $document)
    {
        $object = new Pool();

        $fields = [
            'name'        => 'name',
            'lanemax'     => 'laneMax',
            'lanemin'     => 'laneMin',
            'temperature' => 'temperature',
            'type'        => 'type',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Qualify
     */
    public function extractQualify(SimpleXMLElement $document)
    {
        $object = new Qualify();

        $fields = [
            'conversion' => 'conversion',
            'from'       => 'from',
            'percent'    => 'percent',
            'until'      => 'until',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Session[]
     */
    public function extractSessions(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->SESSION as $value) {
            $objects[] = $this->extractSession($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Session
     */
    public function extractSession(SimpleXMLElement $document)
    {
        $object = new Session();

        $fields = [
            'course'            => 'course',
            'date'              => function (Session $object, $value) {
                $object->date = (new DateTime($value));
            },
            'daytime'           => 'dayTime',
            'endtime'           => 'endTime',
            'EVENTS'            => function (Session $object, $value) {
                $object->events = ($this->extractEvents($value));
            },
            'FEES'              => function (Session $object, $value) {
                $object->fees = ($this->extractFees($value));
            },
            'JUDGES'            => function (Session $object, $value) {
                $object->judges = ($this->extractJudges($value));
            },
            'maxentriesathlete' => 'maxEntriesAthlete',
            'maxentriesrelay'   => 'maxEntriesRelay',
            'name'              => 'name',
            'number'            => 'number',
            'officialmeeting'   => 'officialMeeting',
            'POOL'              => function (Session $object, $value) {
                $object->pool = ($this->extractPool($value));
            },
            'remarksjudge'      => 'remarksJudge',
            'teamleadermeeting' => 'teamLeaderMeeting',
            'timing'            => 'timing',
            'warmupfrom'        => 'warmUpFrom',
            'warmupuntil'       => 'warmUpUntil',
        ];

        return $this->transform($document, $fields, $object);
    }


    /**
     * @param SimpleXMLElement $document
     * @return Event[]
     */
    public function extractEvents(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->EVENT as $value) {
            $objects[] = $this->extractEvent($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Event
     */
    public function extractEvent(SimpleXMLElement $document)
    {
        $object = new Event();

        $fields = [
            'AGEGROUPS'        => function (Event $object, $value) {
                $object->ageGroups = ($this->extractAgeGroups($value));
            },
            'daytime'          => 'dayTime',
            'eventid'          => 'eventId',
            'FEE'              => function (Event $object, $value) {
                $object->fee = ($this->extractFee($value));
            },
            'gender'           => 'gender',
            'HEATS'            => function (Event $object, $value) {
                $object->heats = ($this->extractHeats($value));
            },
            'maxentries'       => 'maxEntries',
            'number'           => 'number',
            'order'            => 'order',
            'preveventid'      => 'prevEventId',
            'round'            => 'round',
            'run'              => 'run',
            'SWIMSTYLE'        => function (Event $object, $value) {
                $object->swimStyle = ($this->extractSwimStyle($value));
            },
            'TIMESTANDARDREFS' => function (Event $object, $value) {
                $object->timeStandardRefs = ($this->extractTimeStandardRefs($value));
            },
            'timing'           => 'timing',
            'type'             => 'type',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("event", $formattedObject, $formattedObject->eventId);
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return AgeGroup[]
     */
    public function extractAgeGroups(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->AGEGROUP as $value) {
            $objects[] = $this->extractAgeGroup($value);
        }

        return $objects;
    }


    public function extractAgeGroup(SimpleXMLElement $document)
    {
        $object = new AgeGroup();

        $fields = [
            'agegroupid' => 'ageGroupId',
            'agemax'     => 'ageMax',
            'agemin'     => 'ageMin',
            'gender'     => 'gender',
            'calculate'  => 'calculate',
            'handicap'   => 'handicap',
            'levelmax'   => 'levelMax',
            'levelmin'   => 'levelMin',
            'levels'     => 'levels',
            'name'       => 'name',
            'RANKINGS'   => function (AgeGroup $object, $value) {
                $object->rankings = ($this->extractRankings($value));
            },
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("ageGroup", $formattedObject, $formattedObject->ageGroupId);
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Ranking[]
     */
    public function extractRankings(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->RANKING as $value) {
            $objects[] = $this->extractRanking($value);
        }

        return $objects;
    }

    /**
     * @param SimpleXMLElement $document
     * @return Ranking
     */
    public function extractRanking(SimpleXMLElement $document)
    {
        $object = new Ranking();

        $fields = [
            'order'    => 'order',
            'place'    => 'place',
            'resultid' => 'resultId',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectForInjection($formattedObject, "result");
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Judge[]
     */
    public function extractJudges(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->JUDGE as $value) {
            $objects[] = $this->extractJudge($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Judge
     */
    public function extractJudge(SimpleXMLElement $document)
    {
        $object = new Judge();

        $fields = [
            'number'     => 'number',
            'officialid' => 'officialId',
            'remarks'    => 'remarks',
            'role'       => 'role',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectForInjection($formattedObject, "official");
	return $formattedObject;
    }

    /**
     * @param SimpleXMLElement $document
     * @return Heat[]
     */
    public function extractHeats(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->HEAT as $value) {
            $objects[] = $this->extractHeat($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return Heat
     */
    public function extractHeat(SimpleXMLElement $document)
    {
        $object = new Heat();

        $fields = [
            'agegroupid' => 'ageGroupId',
            'daytime'    => 'dayTime',
            'final'      => 'final',
            'heatid'     => 'heatId',
            'number'     => 'number',
            'order'      => 'order',
            'status'     => 'status',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("heat", $formattedObject, $formattedObject->heatId);
	$this->registerObjectForInjection($formattedObject, "ageGroup");
	return $formattedObject;
    }

    /**
     * @param SimpleXMLElement $document
     * @return SwimStyle
     */
    public function extractSwimStyle(SimpleXMLElement $document)
    {
        $object = new SwimStyle();

        $fields = [
            'code'        => 'code',
            'distance'    => 'distance',
            'name'        => 'name',
            'relaycount'  => 'relayCount',
            'stroke'      => 'stroke',
            'swimstyleid' => 'swimStyleId',
            'technique'   => 'technique',
        ];

        $formattedObject = $this->transform($document, $fields, $object);
	$this->registerObjectById("swimStyle", $formattedObject, $formattedObject->swimStyleId);
	return $formattedObject;
    }


    /**
     * @param SimpleXMLElement $document
     * @return TimeStandardRef[]
     */
    public function extractTimeStandardRefs(SimpleXMLElement $document)
    {
        $objects = [];

        foreach ($document->TIMESTANDARDREF as $value) {
            $objects[] = $this->extractTimeStandardRef($value);
        }

        return $objects;
    }


    /**
     * @param SimpleXMLElement $document
     * @return TimeStandardRef
     */
    public function extractTimeStandardRef(SimpleXMLElement $document)
    {
        $object = new TimeStandardRef();

        $fields = [
            'timestandardlistid' => 'timeStandardListId',
            'FEE'                => function (TimeStandardRef $object, $value) {
                $object->fee = ($this->extractFee($value));
            },
            'marker'             => 'marker',
        ];

        return $this->transform($document, $fields, $object);
    }


    protected function transform(SimpleXMLElement $document, array $fields, $object)
    {
        foreach ($fields as $key => $value) {
            if (isset($document[$key])) {
                if ($value instanceof Closure) {
                    call_user_func_array($value, [$object, $document[$key]]);
                } else {
		    $object->{$value} = $document[$key]->__toString();
                }
            }

            if (property_exists($document, $key)) {
                if ($value instanceof Closure) {
                    call_user_func_array($value, [$object, $document->$key]);
                } else {
                    $object->{$value} = $document->$key;
                }
            }
        }

        return $object;
    }
}