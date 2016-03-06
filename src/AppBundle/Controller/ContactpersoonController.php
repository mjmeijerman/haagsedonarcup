<?php

namespace AppBundle\Controller;

use AppBundle\Entity\FileUpload;
use AppBundle\Entity\FotoUpload;
use AppBundle\Entity\Jurylid;
use AppBundle\Entity\Nieuwsbericht;
use AppBundle\Entity\Scores;
use AppBundle\Entity\Sponsor;
use AppBundle\Entity\Turnster;
use AppBundle\Entity\User;
use AppBundle\Form\Type\EditSponsorType;
use AppBundle\Form\Type\NieuwsberichtType;
use AppBundle\Form\Type\OrganisatieType;
use AppBundle\Form\Type\SponsorType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Httpfoundation\Response;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Security;
use AppBundle\Entity\Content;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpKernel\Exception;
use AppBundle\Controller\BaseController;
use AppBundle\Form\Type\ContentType;
use Symfony\Component\Validator\Constraints\DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;

/**
 * @Security("has_role('ROLE_CONTACT')")
 */
class ContactpersoonController extends BaseController
{
    /**
     * @Route("/contactpersoon/", name="getContactpersoonIndexPage")
     * @Method("GET")
     */
    public function getIndexPageAction()
    {
        $this->setBasicPageData();
        /** @var User $user */
        $user = $this->getUser();
        $contactgevens = [
            'vereniging' => $user->getVereniging()->getNaam() . ', ' . $user->getVereniging()->getPlaats(),
            'gebruikersnaam' => $user->getUsername(),
            'voornaam' => $user->getVoornaam(),
            'achternaam' => $user->getAchternaam(),
            'email' => $user->getEmail(),
        ];
        $turnsters = [];
        $wachtlijst = [];
        $afgemeld = [];
        /** @var Turnster[] $turnsterObjecten */
        $turnsterObjecten = $user->getTurnster();
        foreach ($turnsterObjecten as $turnsterObject) {
            if ($turnsterObject->getVloermuziek()) {
                $locatie = $turnsterObject->getVloermuziek()->getLocatie();
            } else {
                $locatie = '';
            }
            if ($turnsterObject->getAfgemeld()) {
                $afgemeld[] = [
                    'id' => $turnsterObject->getId(),
                    'voornaam' => $turnsterObject->getVoornaam(),
                    'achternaam' => $turnsterObject->getAchternaam(),
                    'geboorteJaar' => $turnsterObject->getGeboortejaar(),
                    'categorie' => $this->getCategorie($turnsterObject->getGeboortejaar()),
                    'niveau' => $turnsterObject->getNiveau(),
                ];
            } elseif ($turnsterObject->getWachtlijst()) {
                $wachtlijst[] = [
                    'id' => $turnsterObject->getId(),
                    'voornaam' => $turnsterObject->getVoornaam(),
                    'achternaam' => $turnsterObject->getAchternaam(),
                    'geboorteJaar' => $turnsterObject->getGeboortejaar(),
                    'categorie' => $this->getCategorie($turnsterObject->getGeboortejaar()),
                    'niveau' => $turnsterObject->getNiveau(),
                    'vloermuziek' => $locatie,
                ];
            }  else {
                $turnsters[] = [
                    'id' => $turnsterObject->getId(),
                    'voornaam' => $turnsterObject->getVoornaam(),
                    'achternaam' => $turnsterObject->getAchternaam(),
                    'geboorteJaar' => $turnsterObject->getGeboortejaar(),
                    'categorie' => $this->getCategorie($turnsterObject->getGeboortejaar()),
                    'niveau' => $turnsterObject->getNiveau(),
                    'wedstrijdnummer' => $turnsterObject->getScores()->getWedstrijdnummer(),
                    'vloermuziek' => $locatie,
					'opmerking' => $turnsterObject->getOpmerking(),
                ];
            }
        }
        $juryleden = [];
        /** @var Jurylid[] $juryObjecten */
        $juryObjecten = $user->getJurylid();
        foreach ($juryObjecten as $juryObject) {
            $juryleden[] = [
                'voornaam' => $juryObject->getVoornaam(),
                'achternaam' => $juryObject->getAchternaam(),
                'opmerking' => $juryObject->getOpmerking(),
                'brevet' => $juryObject->getBrevet(),
            ];
        }
        return $this->render('contactpersoon/contactpersoonIndex.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
            'contactgegevens' => $contactgevens,
            'turnsters' => $turnsters,
            'wachtlijstTurnsters' => $wachtlijst,
            'afgemeldTurnsters' => $afgemeld,
            'juryleden' => $juryleden,
        ));
    }

    /**
     * @Route("/contactpersoon/addTurnster/", name="addTurnster")
     * @Method({"GET", "POST"})
     */
    public function addTurnster(Request $request)
    {
        $this->setBasicPageData();
        $turnster = [
            'voornaam' => '',
            'achternaam' => '',
            'geboortejaar' => '',
            'niveau' => '',
            'opmerking' => '',
        ];
        $classNames = [
            'voornaam' => 'text',
            'achternaam' => 'text',
            'geboortejaar' => 'turnster_niveau',
            'niveau' => 'turnster_niveau',
            'opmerking' => 'text',
        ];
        $geboorteJaren = $this->getGeboorteJaren();
        $vrijePlekken = $this->getVrijePlekken();
        $csrfToken = $this->getToken();
        if ($request->getMethod() == 'POST') {
            $turnster = [
                'voornaam' => $request->request->get('voornaam'),
                'achternaam' => $request->request->get('achternaam'),
                'geboortejaar' => $request->request->get('geboorteJaar'),
                'niveau' => $request->request->get('niveau'),
                'opmerking' => $request->request->get('opmerking'),
            ];
            $postedToken = $request->request->get('csrfToken');
            if (!empty($postedToken)) {
                if ($this->isTokenValid($postedToken)) {
                    $validationTurnster = [
                        'voornaam' => false,
                        'achternaam' => false,
                        'geboorteJaar' => false,
                        'niveau' => false,
                        'opmerking' => true,
                    ];

                    $classNames['opmerking'] = 'succesIngevuld';

                    if (strlen($request->request->get('voornaam')) > 1) {
                        $validationTurnster['voornaam'] = true;
                        $classNames['voornaam'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geldige voornaam ingevoerd'
                        );
                        $classNames['voornaam'] = 'error';
                    }

                    if (strlen($request->request->get('achternaam')) > 1) {
                        $validationTurnster['achternaam'] = true;
                        $classNames['achternaam'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geldige achternaam ingevoerd'
                        );
                        $classNames['achternaam'] = 'error';
                    }
                    if ($request->request->get('geboorteJaar')) {
                        $validationTurnster['geboorteJaar'] = true;
                        $classNames['geboortejaar'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen geboortejaar ingevoerd'
                        );
                        $classNames['geboortejaar'] = 'error';
                    }

                    if ($request->request->get('niveau')) {
                        $validationTurnster['niveau'] = true;
                        $classNames['niveau'] = 'succesIngevuld';
                    } else {
                        $this->addFlash(
                            'error',
                            'geen niveau ingevoerd'
                        );
                        $classNames['niveau'] = 'error';
                    }
                    if (!(in_array(false, $validationTurnster))) {
                        $turnster = new Turnster();
                        $scores = new Scores();
                        if ($this->getVrijePlekken() > 0) {
                            $turnster->setWachtlijst(false);
                        } else {
                            $turnster->setWachtlijst(true);
                        }
                        $turnster->setCreationDate(new \DateTime('now'));
                        $turnster->setExpirationDate(null);
                        $turnster->setScores($scores);
                        $turnster->setUser($this->getUser());
                        $turnster->setIngevuld(true);
                        $turnster->setVoornaam($request->request->get('voornaam'));
                        $turnster->setAchternaam($request->request->get('achternaam'));
                        $turnster->setGeboortejaar($request->request->get('geboorteJaar'));
                        $turnster->setNiveau($request->request->get('niveau'));
                        $turnster->setOpmerking($request->request->get('opmerking'));
                        $this->getUser()->addTurnster($turnster);
                        $this->addToDB($this->getUser());
                        return $this->redirectToRoute('getContactpersoonIndexPage');
                    }
                }
            }
        }
        return $this->render('contactpersoon/addTurnster.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' => $this->sponsors,
            'vrijePlekken' => $vrijePlekken,
            'turnster' => $turnster,
            'geboorteJaren' => $geboorteJaren,
            'classNames' => $classNames,
            'csrfToken' => $csrfToken,
        ));
    }
}
