<?php

namespace Thapnath\CleanBlogBundle\Controller;

use eZ\Bundle\EzPublishCoreBundle\Controller;
use eZ\Publish\API\Repository\Values\Content\Location;
use eZ\Publish\Core\Pagination\Pagerfanta\ContentSearchAdapter;
use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;

class BlogController extends Controller
{

    /**
     * Displays the list of blog_post
     * Note: This is a fully customized controller action, it will generate the response and call
     *       the view. Since it is not calling the ViewControler we don't need to match a specific
     *       method signature.
     *
     * @param int $locationId of a blog
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function listBlogPostsAction($locationId)
    {
        $response = new Response();

        // Setting default cache configuration (you can override it in you siteaccess config)
        $response->setSharedMaxAge($this->getConfigResolver()->getParameter('content.default_ttl'));

        // Make the response location cache aware for the reverse proxy
        $response->headers->set('X-Location-Id', $locationId);
        $response->setVary('X-User-Hash');

        $viewParameters = $this->getRequest()->attributes->get('viewParameters');

        // TODO keyword search is not implemented in the public API yet, so we forward to a legacy view
        if (!empty($viewParameters['tag']))
        {
            $tag = $viewParameters['tag'];

            return $this->redirect(
                            $this->generateUrl(
                                    'ez_legacy', array('module_uri' => '/content/keyword/' . $tag)
                            )
            );
        }

        // Getting location and content from ezpublish dedicated services
        $repository = $this->getRepository();
        $location = $repository->getLocationService()->loadLocation($locationId);
        if ($location->invisible)
        {
            throw new NotFoundHttpException("Location #$locationId cannot be displayed as it is flagged as invisible.");
        }

        $content = $repository
                ->getContentService()
                ->loadContentByContentInfo($location->getContentInfo());

        // Getting language for the current siteaccess
        $languages = $this->getConfigResolver()->getParameter('languages');

        // Using the criteria helper (a demobundle custom service) to generate our query's criteria.
        // This is a good practice in order to have less code in your controller.
        $criteria = $this->get('thapnath_clean_blog.criteria_helper')->generateListWebsitePostCriterion(
                $location, $viewParameters, $languages
        );

        // Generating query
        $query = new Query();
        $query->criterion = $criteria;
        $query->sortClauses = array(
            new SortClause\Field('blog_post', 'publication_date', Query::SORT_DESC, $languages[0])
        );

        // Initialize pagination.
        $pager = new Pagerfanta(
                new ContentSearchAdapter($query, $this->getRepository()->getSearchService())
        );
        $pager->setMaxPerPage($this->container->getParameter('thapnath_clean_blog.blog.blog_post_list.limit'));
        $pager->setCurrentPage($this->getRequest()->get('page', 1));

        return $this->render(
                        'ThapnathCleanBlogBundle:full:blog.html.twig', array(
                    'location' => $location,
                    'content' => $content,
                    'pagerBlog' => $pager
                        ), $response
        );
    }

    /**
     * Action used to display a blog_post
     *  - Adds the content's author to the response.
     * Note: This is a partly customized controller action. It is executed just before the original
     *       Viewcontroller's viewLocation method. To be able to do that, we need to implement it's
     *       full signature.
     *
     * @param $locationId
     * @param $viewType
     * @param bool $layout
     * @param array $params
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function showBlogPostAction($locationId, $viewType, $layout = false, array $params = array())
    {
        // We need the author, whatever the view type is.
        $repository = $this->getRepository();
        $location = $repository->getLocationService()->loadLocation($locationId);
        $author = $repository->getUserService()->loadUser($location->getContentInfo()->ownerId);

        // TODO once the keyword service is available, load the number of keyword for each keyword
        // Delegate view rendering to the original ViewController
        // (makes it possible to continue using defined template rules)
        // We just add "author" to the list of variables exposed to the final template
        return $this->get('ez_content')->viewLocation(
                        $locationId, $viewType, $layout, array('author' => $author)
        );
    }

}
