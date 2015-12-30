<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Nieuwsbericht;
use AppBundle\Entity\Sponsor;
use AppBundle\Form\Type\NieuwsberichtType;
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
 * @Security("has_role('ROLE_ADMIN')")
 */
class AdminController extends BaseController
{
    /**
     * @Route("/admin/", name="getAdminIndexPage")
     * @Method("GET")
     */
    public function getIndexPageAction()
    {
        $this->setBasicPageData();
        return $this->render('inloggen/adminIndex.html.twig', array(
            'menuItems' => $this->menuItems,
            'sponsors' =>$this->sponsors,
        ));
    }

    /**
     * @Route("/pagina/{page}", name="setContent")
     * @Method("POST")
     */
    public function updateContentAction($page, Request $request)
    {
        if ($this->checkIfPageExists($page)) {
            switch ($page) {
                default:
                    $content = new Content();
                    $content->setGewijzigd(new \DateTime("now"));
                    $content->setPagina($page);
                    $content->setContent($request->request->get('content'));
                    $em = $this->getDoctrine()->getManager();
                    $em->persist($content);
                    $em->flush();
                    return $this->redirectToRoute('getContent', array('page' => $page));
            }
        } else {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' =>$this->sponsors,
            ));
        }
    }

    /**
     * @Route("/pagina/{page}/edit/", defaults={"page" = "geschiedenis"}, name="editDefaultPage")
     * @Method({"GET", "POST"})
     */
    public function editDefaultPageAction($page, Request $request)
    {
        $this->setBasicPageData();
        if ($this->checkIfPageExists($page)) {
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT content
            FROM AppBundle:Content content
            WHERE content.pagina = :page
            ORDER BY content.gewijzigd DESC')
                ->setParameter('page', $page);
            /** @var Content $content */
            $result = $query->setMaxResults(1)->getOneOrNullResult();
            $content = "";
            if (count($result) == 1) $content = $result->getContent();
            {
                $form = $this->createForm(new ContentType(), $content);
                $form->handleRequest($request);

                if ($form->isValid()) {
                    $editedContent = new Content();
                    $editedContent->setGewijzigd(new \DateTime('NOW'));
                    $editedContent->setPagina($page);
                    $editedContent->setContent($content->getContent());
                    $em->detach($content);
                    $em->persist($editedContent);
                    $em->flush();
                    return $this->redirectToRoute('getContent', array('page' => $page));
                }
                else {
                    return $this->render('default/editIndex.html.twig', array(
                        'content' => $content->getContent(),
                        'menuItems' => $this->menuItems,
                        'form' => $form->createView(),
                        'sponsors' =>$this->sponsors,
                    ));
                }
            }
            else
            {
                return $this->render('error/pageNotFound.html.twig', array(
                    'menuItems' => $this->menuItems,
                    'sponsors' =>$this->sponsors,
                ));
            }

        }
        else
        {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' =>$this->sponsors,
            ));
        }
    }

    /**
     * @Route("/pagina/Laatste%20nieuws/add/", name="addNieuwsPage")
     * @Method({"GET", "POST"})
     */
    public function addNieuwsPage(Request $request)
    {
        $this->setBasicPageData();
        $nieuwsbericht = new Nieuwsbericht();
        $form = $this->createForm(new NieuwsberichtType(), $nieuwsbericht);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $nieuwsbericht->setDatumtijd(date('d-m-Y: H:i', time()));
            $nieuwsbericht->setJaar(date('Y', time()));
            $nieuwsbericht->setBericht(str_replace("\n","<br />",$nieuwsbericht->getBericht()));
            $em = $this->getDoctrine()->getManager();
            $em->persist($nieuwsbericht);
            $em->flush();
            return $this->redirectToRoute('getContent', array('page' => 'Laatste nieuws'));
        }
        else {
            return $this->render('default/addNieuwsbericht.html.twig', array(
                'form' => $form->createView(),
                'menuItems' => $this->menuItems,
                'sponsors' =>$this->sponsors,
            ));
        }
    }

    /**
     * @Route("/pagina/Laatste%20nieuws/edit/{id}/", name="editNieuwsberichtPage")
     * @Method({"GET", "POST"})
     */
    public function editNieuwsberichtPage($id, Request $request)
    {
        $this->setBasicPageData();
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT nieuwsbericht
            FROM AppBundle:Nieuwsbericht nieuwsbericht
            WHERE nieuwsbericht.id = :id')
            ->setParameter('id', $id);
        /** @var Nieuwsbericht $nieuwsbericht */
        $nieuwsbericht = $query->setMaxResults(1)->getOneOrNullResult();
        if(count($nieuwsbericht) > 0)
        {
            $nieuwsbericht->setBericht(str_replace("<br />","\n",$nieuwsbericht->getBericht()));
            $form = $this->createForm(new NieuwsberichtType(), $nieuwsbericht);
            $form->handleRequest($request);

            if ($form->isValid()) {
                $nieuwsbericht->setBericht(str_replace("\n","<br />",$nieuwsbericht->getBericht()));
                $em = $this->getDoctrine()->getManager();
                $em->persist($nieuwsbericht);
                $em->flush();
                return $this->redirectToRoute('getContent', array('page' => 'Laatste nieuws'));
            }
            else {
                return $this->render('default/addNieuwsbericht.html.twig', array(
                    'form' => $form->createView(),
                    'menuItems' => $this->menuItems,
                    'sponsors' =>$this->sponsors,
                ));
            }
        }
        else
        {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' =>$this->sponsors,
            ));
        }
    }

    /**
     * @Route("/pagina/Laatste%20nieuws//remove/{id}/", name="removeNieuwsberichtPage")
     * @Method({"GET", "POST"})
     */
    public function removeNieuwsberichtPage($id, Request $request)
    {
        if($request->getMethod() == 'GET')
        {
            $this->setBasicPageData();
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT nieuwsbericht
                FROM AppBundle:Nieuwsbericht nieuwsbericht
                WHERE nieuwsbericht.id = :id')
                ->setParameter('id', $id);
            $nieuwsbericht = $query->setMaxResults(1)->getOneOrNullResult();
            if(count($nieuwsbericht) > 0)
            {
                return $this->render('default/removeNieuwsbericht.html.twig', array(
                    'content' => $nieuwsbericht->getAll(),
                    'menuItems' => $this->menuItems,
                    'sponsors' =>$this->sponsors,
                ));
            }
            else
            {
                return $this->render('error/pageNotFound.html.twig', array(
                    'menuItems' => $this->menuItems,
                    'sponsors' =>$this->sponsors,
                ));
            }
        }
        elseif($request->getMethod() == 'POST')
        {
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT nieuwsbericht
                FROM AppBundle:Nieuwsbericht nieuwsbericht
                WHERE nieuwsbericht.id = :id')
                ->setParameter('id', $id);
            $nieuwsbericht = $query->setMaxResults(1)->getOneOrNullResult();
            $em->remove($nieuwsbericht);
            $em->flush();
            return $this->redirectToRoute('getContent', array('page' => 'Laatste nieuws'));
        }
        else
        {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' =>$this->sponsors,
            ));
        }
    }

    /**
     * @Template()
     * @Route("/pagina/Sponsors/add/", name="addSponsorPage")
     * @Method({"GET", "POST"})
     */
    public function addSponsorPageAction(Request $request)
    {
        $this->setBasicPageData();
        $sponsor = new Sponsor();
        $form = $this->createFormBuilder($sponsor)
            ->add('naam', null, array(
                'required' => true
            ))
            ->add('file', null, array(
                'required' => true
            ))
            ->add('file2', null, array(
                'required' => true
            ))
            ->add('website')
            ->add('omschrijving')
            ->add('opslaan', 'submit')

            ->getForm();
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($sponsor);
            $em->flush();
            $this->get('helper.imageresizer')->resizeImage($sponsor->getAbsolutePath(), $sponsor->getUploadRootDir()."/" , null, $width=597);
            return $this->redirectToRoute('getContent', array('page' => 'Sponsors'));
        }
        else {
            return $this->render('default/addSponsor.html.twig', array(
                'form' => $form->createView(),
                'menuItems' => $this->menuItems,
                'sponsors' => $this->sponsors,
            ));
        }
    }

    /**
     * @Route("/pagina/Sponsors/edit/{id}/", name="editSponsorPage")
     * @Method({"GET", "POST"})
     */
    public function editSponsorPage($id, Request $request)
    {
        $this->setBasicPageData();
        $em = $this->getDoctrine()->getManager();
        $query = $em->createQuery(
            'SELECT sponsor
            FROM AppBundle:Sponsor sponsor
            WHERE sponsor.id = :id')
            ->setParameter('id', $id);
        $sponsor = $query->setMaxResults(1)->getOneOrNullResult();
        if (count($sponsor) > 0) {
            $form = $this->createFormBuilder($sponsor)
                ->add('naam')
                ->add('website')
                ->add('omschrijving')
                ->add('opslaan', 'submit')
                ->getForm();
            $form->handleRequest($request);

            if ($form->isValid()) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($sponsor);
                $em->flush();
                return $this->redirectToRoute('getContent', array('page' => 'Sponsors'));
            } else {
                return $this->render('default/addSponsor.html.twig', array(
                    'form' => $form->createView(),
                    'menuItems' => $this->menuItems,
                    'sponsors' => $this->sponsors,
                ));
            }
        }
    }

    /**
     * @Route("/pagina/Sponsors/remove/{id}/", name="removeSponsorPage")
     * @Method({"GET", "POST"})
     */
    public function removeSponsorPage($id, Request $request)
    {
        if($request->getMethod() == 'GET')
        {
            $this->setBasicPageData();
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT sponsor
                FROM AppBundle:Sponsor sponsor
                WHERE sponsor.id = :id')
                ->setParameter('id', $id);
            $sponsor = $query->setMaxResults(1)->getOneOrNullResult();
            if(count($sponsor) > 0)
            {
                return $this->render('default/removeSponsor.html.twig', array(
                    'content' => $sponsor->getAll(),
                    'menuItems' => $this->menuItems,
                    'sponsors' => $this->sponsors,
                ));
            }
            else
            {
                return $this->render('error/pageNotFound.html.twig', array(
                    'menuItems' => $this->menuItems,
                    'sponsors' => $this->sponsors,
                ));
            }
        }
        elseif($request->getMethod() == 'POST')
        {
            $em = $this->getDoctrine()->getManager();
            $query = $em->createQuery(
                'SELECT sponsor
                FROM AppBundle:Sponsor sponsor
                WHERE sponsor.id = :id')
                ->setParameter('id', $id);
            $sponsor = $query->setMaxResults(1)->getOneOrNullResult();
            $em->remove($sponsor);
            $em->flush();
            return $this->redirectToRoute('getContent', array('page' => 'Sponsors'));
        }
        else
        {
            return $this->render('error/pageNotFound.html.twig', array(
                'menuItems' => $this->menuItems,
                'sponsors' => $this->sponsors,
            ));
        }
    }

}
