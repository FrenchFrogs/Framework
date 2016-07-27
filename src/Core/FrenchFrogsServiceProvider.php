<?php namespace FrenchFrogs\Core;

use Illuminate\Support\ServiceProvider;
use Illuminate\Mail;
use FrenchFrogs;
use Response, Request, Route, Input, Blade, Auth;

class FrenchFrogsServiceProvider  extends ServiceProvider
{


    /**
     * Boot principale du service
     *
     */
    public function boot()
    {
        $this->bootBlade();
        $this->bootDatatable();
        $this->bootModal();
        $this->bootMail();
        $this->bootValidator();
        $this->publish();
    }

    /**
     * Register bindings in the container.
     *
     * @return void
     */
    public function register()
    {
        foreach(config('frenchfrogs') as $namespace => $config) {
            configurator($namespace)->merge($config);
        }
    }

    /**
     * Ajoute de nouveau validateur
     *
     */
    public function bootValidator()
    {

        // notExists (database) renvoie true si l'entrée n'existe pas
        \Validator::extend('not_exists', function($attribute, $value, $parameters)
        {
            $row = \DB::table($parameters[0])->where($parameters[1], '=', $value)->first();
            return empty($row);
        });
    }

    /**
     * Affichage de nombre formaté
     */
    public function bootBlade()
    {
        Blade::directive('number', function($expression, $decimals = 0) {
            return "<?php echo number_format({$expression}, {$decimals}) ?>";
        });
    }

    /**
     * Datatable render
     * @param string $route
     */
    public function bootDatatable($route = '/datatable/{token}')
    {

        // gestion de la navifgation Ajax
        Route::get($route, function($token) {

            try{
                $request = request();

                // chargement de l'objet
                $table = FrenchFrogs\Table\Table\Table::load($token);

                // configuration de la navigation
                $table->setItemsPerPage(Input::get('length'));
                $table->setPageFromItemsOffset(Input::get('start'));
                $table->setRenderer(new FrenchFrogs\Table\Renderer\Remote());

                // gestion des reccherches
                foreach(request()->get('columns') as $c) {
                    if ($c['searchable'] == "true" && $c['search']['value'] != '') {
                        $table->getColumn($c['name'])->getStrainer()->call($table, $c['search']['value']);
                    }
                }

                // gestion de la recherche globale
                $search = $request->get('search');
                if (!empty($search['value']) ) {
                    $table->search($search['value']);
                }

                // gestion du tri
                $order = $request->get('order');
                if (!empty($order)) {

                    if ($table->isSourceQueryBuilder()) {
                        $table->getSource()->orders = [];
                    }

                    foreach($order as $o) {
                        extract($o);
                        $table->getColumnByIndex($column)->order($dir);
                    }
                }

                // recuperation des données
                $data = [];
                foreach($table->render() as $row){
                    $data[] = array_values($row);
                }

                return response()->json(['data' => $data, 'draw' => Input::get('draw'), 'recordsFiltered' => $table->getItemsTotal(), 'recordsTotal' => $table->getItemsTotal()]);

            }catch (\Exception $e) {
                //Si on catch une erreur on renvoi une reponse json avec le code 500
                return response()->json(['error' => $e->getMessage()],500);
            }

        })->name('datatable');


        /**
         * Gestion de l'export CSV
         */
        Route::get($route . '/export', function($token) {
            $table = FrenchFrogs\Table\Table\Table::load($token);
            $table->setItemsPerPage(5000);
            $table->toCsv();
        })->name('datatable-export');


        /**
         * Gestion de l'edition en remote
         *
         */
        Route::post($route, function($token) {
            $request = request();
            $table = FrenchFrogs\Table\Table\Table::load($token);
            return $table->getColumn($request->get('column'))->remoteProcess($request->get('id'), $request->get('value'));
        });

    }

    /**
     * Modal Manager
     *
     * Gestion des reponse ajax qui s'affiche dans une modal
     *
     */
    public function bootModal()
    {
        Response::macro('modal', function($title, $body = '', $actions  = [])
        {
            if ($title instanceof FrenchFrogs\Modal\Modal\Modal) {
                $modal = $title->enableRemote();
            } elseif($title instanceof FrenchFrogs\Form\Form\Form) {
                $renderer = configurator()->get('form.renderer.modal.class');
                $modal = $title->setRenderer(new $renderer());
            } else {
                $modal = modal($title, $body, $actions)->enableRemote();
            }

            $modal .= '<script>jQuery(function() {'.js('onload').'});</script>';

            return $modal;
        });
    }

    /**
     * Permet d'utiliser la reponse pour envoyer un mail
     *
     * Cela permet de gere un email comme une page web.
     *
     */
    public function bootMail()
    {
        /**
         * Mail manager
         *
         * @param string $view
         * @param array $data
         * @param array $from
         * @param array $to
         * @param string $subject
         * @param array $attach
         */
        Response::macro('mail', function($view, $data = [], $from = [], $to = [], $subject = '', $attach = []) {

            if ($from instanceof Mail\Message) {
                \Mail::send($view, $data, function (Mail\Message $message) use ($from, $attach) {

                    // Getting the generated message
                    $swift = $from->getSwiftMessage();

                    // Setting the mandatory arguments
                    $message->subject($swift->getSubject());
                    $message->from($swift->getFrom());
                    $message->to($swift->getTo());

                    // Getting the optional arguments
                    if (!empty($swift->getSender()))    { $message->sender($swift->getSender()); }
                    if (!empty($swift->getCc()))        { $message->cc($swift->getCc()); }
                    if (!empty($swift->getBcc()))       { $message->bcc($swift->getBcc()); }
                    if (!empty($swift->getReplyTo()))   { $message->replyTo($swift->getReplyTo()); }
                    if (!empty($swift->getPriority()))  { $message->priority($swift->getPriority()); }
                    if (!empty($swift->getChildren()))  {
                        foreach($swift->getChildren() as $child){
                            $message->attachData($child->getBody(),$child->getHeaders()->get('content-type')->getParameter('name'));
                        }
                    }
                });

            } else {
                \Mail::send($view, $data, function (Mail\Message $message) use ($from, $to, $subject, $attach) {
                    // Setting the mandatory arguments
                    $message->from(...$from)->subject($subject);

                    // Managing multiple to
                    foreach ($to as $mail)      { $message->to(...$mail); }
                    // Managing multiple attachment
                    foreach ($attach as $file)  { $message->attach($file); }
                });
            }
        });
    }

    /**
     * Publish asset frenchfrogs
     *
     * @deprecated J'aimerai vraiement m'en passer, j'y reflechirais plus tard
     *
     */
    public function publish(){
        // Asset management
        $this->publishes([
            __DIR__.'/../../assets' => base_path('resources/assets'),
        ], 'frenchfrogs');
    }
}