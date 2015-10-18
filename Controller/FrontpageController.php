<?php

namespace Thapnath\CleanBlogBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use Symfony\Component\HttpFoundation\Response;

class FrontpageController extends Controller
{

    public function mainBlockAction()
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
        $latestContent = $this->get('thapnath_clean_blog.content_helper')->getTopContent();

        $locationList = array();
        // Looping against search results to build $locationList
        // Both arrays will be indexed by contentId so that we can easily refer to an element in a list from another element in the other list
        foreach ($latestContent as $contentId => $content)
        {
            $locationList[$contentId] = $this->getRepository()->getLocationService()->loadLocation($content->contentInfo->mainLocationId);
        }
        return $this->render(
                        'ThapnathCleanBlogBundle:frontpage:mainblock.html.twig', array(
                    'top_content' => $latestContent,
                    'locationList' => $locationList
                        ), $response
        );
    }

}
