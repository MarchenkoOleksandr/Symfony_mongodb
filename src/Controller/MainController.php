<?php

namespace App\Controller;

use Exception;
use MongoDB\BSON\ObjectId;
use MongoDB\Client;
use MongoDB\Collection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class MainController
 *
 * @package App\Controller
 */
class MainController extends AbstractController
{
    /**
     * @Route("/", name="index", methods={"GET"})
     * @param Request $request
     *
     * @return Response
     */
    public function index(Request $request)
    {
        $findString = $request->query->get('findString');
        $findArray  = json_decode($findString, true) ?? [];

        try {
            $collection = $this->getMongoClient();
            $entities   = $collection->find($findArray)->toArray();
        }
        finally {
            return $this->render('main/index.html.twig', [
                'mongo_entities'        => $entities ?? [],
                'findString'            => $findString,
                'isFindStringIncorrect' => !empty($findString) && empty($findArray)
            ]);
        }
    }

    /**
     * @Route("/show/{id}", name="show", methods={"GET"})
     *
     * @param string $id
     *
     * @return Response
     */
    public function show(string $id): Response
    {
        $entity = $this->getMongoClient()->findOne(['_id' => new ObjectId($id)]);

        return $this->render('main/show.html.twig', [
            'mongo_entity' => $entity,
        ]);
    }

    /**
     * @Route("/new", name="new", methods={"GET","POST"})
     * @param Request $request
     *
     * @return Response
     * @throws Exception
     */
    public function new(Request $request): Response
    {
        $mongoEntity = [];
        $form        = $this->createFormBuilder($mongoEntity)
                            ->add('mongoEntity', TextareaType::class)
                            ->getForm();
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->getData()['mongoEntity']);
            if (!$array) {
                throw new Exception('JSON error! Please check JSON structure!');
            }

            $collection = $this->getMongoClient();
            if ($collection) {
                $collection->insertOne($array);
            }

            return $this->redirectToRoute('index');
        }

        return $this->render('main/new.html.twig', [
            'mongo_entity' => $mongoEntity,
            'form'         => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}/edit", name="edit", methods={"GET","POST"})
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     * @throws Exception
     */
    public function edit(Request $request, string $id): Response
    {
        $mongoEntity = $this->getMongoClient()->findOne(['_id' => new ObjectId($id)]);

        $mongoEntityClone = json_decode(json_encode($mongoEntity), true);
        unset($mongoEntityClone['_id']);

        $form = $this->createForm(TextareaType::class, json_encode($mongoEntityClone));
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $array = json_decode($form->getData());
            if (!$array) {
                throw new Exception('Json error! Please check JSON structure!');
            }

            $this->getMongoClient()->replaceOne(['_id' => new ObjectId($id)], $array);

            return $this->redirectToRoute('index');
        }

        return $this->render('main/edit.html.twig', [
            'mongo_entity' => $mongoEntity,
            'form'         => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="delete", methods={"DELETE"})
     * @param Request $request
     * @param string  $id
     *
     * @return Response
     */
    public function delete(Request $request, string $id): Response
    {
        if ($this->isCsrfTokenValid('delete' . $id, $request->request->get('_token'))) {
            $this->getMongoClient()->deleteOne(['_id' => new ObjectId($id)]);
        }

        return $this->redirectToRoute('index');
    }

    /**
     * @Route("/dbsettings", name="db_settings", methods={"GET","POST"})
     * @param Request $request
     *
     * @return Response
     */
    public function dbSettings(Request $request): Response
    {
        if ($request->getMethod() == 'GET') {
            return $this->render('main/db_settings.html.twig', ['dbParameters' => $this->getDbParameters()]);
        }

        $this->setCookies($request);

        return $this->redirectToRoute('index');
    }

    /**
     * @return Collection|null
     */
    private function getMongoClient()
    {
        [$connectionString, $dbName, $collectionName] = array_values($this->getDbParameters());

        try {
            return (new Client($connectionString))->$dbName->$collectionName;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * @param Request $request
     */
    private function setCookies(Request $request)
    {
        setcookie("mongoDb[connectionString]", $request->request->get('connection_string'));
        setcookie("mongoDb[dbName]", $request->request->get('db_name'));
        setcookie("mongoDb[collectionName]", $request->request->get('collection_name'));
    }

    /**
     * @return array
     */
    private function getDbParameters()
    {
        return [
            'connectionString' => $_COOKIE['mongoDb']['connectionString'] ?? '',
            'dbName'           => $_COOKIE['mongoDb']['dbName'] ?? '',
            'collectionName'   => $_COOKIE['mongoDb']['collectionName'] ?? '',
        ];
    }
}
