<?php

namespace Thapnath\CleanBlogBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

class DefaultController extends Controller
{

    public function topMenuAction()
    {
        $rootLocationId = $this->getConfigResolver()->getParameter('content.tree_root.location_id');
        // Setting HTTP cache for the response to be public and with a TTL of 1 day.
        $response = new Response;
        $response->setPublic();
        $response->setSharedMaxAge(86400);
        // Menu will expire when top location cache expires.
        $response->headers->set('X-Location-Id', $rootLocationId);
        // Retrieve latest content through the ContentHelper.
        // We only want articles that are located somewhere in the tree under root location.
        $topMenu = $this->get('thapnath_clean_blog.content_helper')->getTopMenu();

        return $this->render('ThapnathCleanBlogBundle:parts:top_menu.html.twig', array(
                    'top_menu' => $topMenu,
                        ), $response
        );
    }

}
