<?php

class ApiController extends AbstractApiController
{

    /**
     * Parsing request and execute the action
     */
    public function route()
    {

        // Les actions disponible
        // array(ACTION=>array(EXAMPLE_URL))
        $actions = array(
            'feed' => array('feeds?pretty=1'),
            'feeds' => array('feeds?full=1&pretty=1'),
            'latest' => array('latest?pretty=1','latest?format=rss'),
            'top' => array('top?interval=48h&pretty=1'),
            'search' => array('search?q=sebsauvage&pretty=1','search?c=1&q=a_category&pretty=1'),
            'discussion' => array('discussion?url=https://exemple.com/shaarli-river/index.php&pretty=1'),
            'bestlinks' => array('bestlinks'),
            'random' => array('random?limit=10&pretty=1'),
            'keywords' => array('keywords'),
            'getfavicon' => array('getfavicon?id=1')
        );

        if (defined('ALLOW_WEB_SYNC') && ALLOW_WEB_SYNC) {
            $actions['syncfeeds'] = array('syncfeeds');
        }

        if (defined('ALLOW_WEB_PING') && ALLOW_WEB_PING) {
            $actions['ping'] = array('ping?url=https://exemple.com');
        }

        $action = $this->getRequestAction();
        if ($action == "") {
            $this->outputJSON(
                array(
                    'success' => 1,
                    'api' => API_SOURCE_CODE,
                    'actions' => $actions
                )
            );
            exit;
        } elseif (!isset($actions[$action])) {
            $this->error('Bad request (invalid action)');
        }

        // Les formats disponibles
        $formats = array(
            'json',
            'rss',
            'opml',
        );

        // Default format: json
        $format = (isset($_GET['format']) && in_array($_GET['format'], $formats)) ? $_GET['format']: 'json';

        $arguments = $_GET;

        if ($action == 'syncfeeds') {
            $this->syncfeeds();
            $this->outputJSON(array('success' => 1));
        }

        $api = new ShaarliApi();

        if (method_exists($api, $action)) {
            try {
                // Execute the action
                $results = $api->$action($arguments);
            } catch (ShaarliApiException $e) {
                $this->error($e->getMessage());
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
        } else {
            $this->error('Bad request (invalid action)');
        }

        if ($action == 'getfavicon') {
            if (isset($results['favicon']) && file_exists($results['favicon'])) {
                header('Content-Type: image/png');
                header('Cache-Control: private, max-age=10800, pre-check=10800');
                header('Pragma: private');
                header('Expires: ' . date(DATE_RFC822, strtotime('7 day')));

                readfile($results['favicon']);
            }

            exit();
        }

        // Render results
        if ($format == 'json') {
            $this->outputJSON($results, (isset($_GET['pretty']) && $_GET['pretty'] == 1));
        }
        // RSS
        elseif ($format == 'rss') {
            if ($action == 'latest') {
                $config = array(
                    'title' => 'Shaarli API - Latest entries',
                );
            } elseif ($action == 'search') {
                $config = array(
                    'title' => 'Shaarli API - Search feed',
                );
            } elseif ($action == 'bestlinks') {
                $config = array(
                    'title' => 'Shaarli API - Best links',
                );
            } else {
                $this->error('Bad request (RSS format unavailable for this action)');
                exit();
            }

            $this->outputRSS($results, $config);
            exit();
        }
        // OPML
        elseif ($format == 'opml') {
            if ($action == 'feeds') {
                $this->outputOPML($results);
                exit();
            } else {
                $this->error('Bad request (OPML format unavailable for this action)');
            }
        }

        exit();
    }

    /**
     * syncfeeds action
     * @return int Number of new feeds
     */
    public function syncfeeds()
    {
        $api = new ShaarliApi();
        $api->addBlacklist();
        $count = $api->syncfeeds(shaarli_api_nodes());
        $count += $api->syncWithOpmlFiles(shaarli_opml_files());
        return $count;
    }
}
