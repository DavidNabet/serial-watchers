<?php

namespace App\Controller;

use App\Entity\Serie;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Common\Collections\Collection;

class SeriesController extends AbstractController
{
    //------------------- AFFICHAGE DES SERIES / FILTRES / PAGINATION ------------------//

    /**
     * @Route("/series")
     */
    public function api(Request $request)
    {
        //La clé API
        $api = "f9966f8cc78884142eed6c6d4710717a";

        // La taille de l'image
        $size = "w342";
        // concaténer avec l'url de l'image
        $baseURI = "http://image.tmdb.org/t/p/". $size;

        // page de résultat
        if ($request->query->has('page')) {
            $page = $request->query->get('page');
        } else {
            $page = 1;
        }

        /*if ($request->query->has('sort_by')) {
            $genre = $request->query->has('sort_by');


            if ($sort == 'Note ascendante'){
                $sort = 'vote_average.asc';
            } elseif($sort == 'Popularité ascendante') {
                $sort = 'popularity.asc';
            }

        } else {
            $sort = "first_air_date.desc";
        }*/

        // tri
        // Est-ce que l'url possède le champ sort_by qui permet de trier
        if ($request->query->has('sort_by')) {
            // Récupérer la valeur selectionnée de la <select> list
            $tri = $request->query->get('sort_by');
        } else {
            $tri = "popularity.desc";
        }


        //tri par année
        if($request->query->has('first_air_date_year')) {
            $annee = $request->query->get('first_air_date_year');
        } else {
            $annee = 2018;
        }

        //tri par genre
        if($request->query->has('with_genres')) {
            $genre = $request->query->get('with_genres');
        } else {
            $genre = 0;
        }


        //appel à l'api
        $json = file_get_contents("https://api.themoviedb.org/3/discover/tv?api_key=".$api."&language=fr-FR&page=".$page."&with_genres=".$genre."&sort_by=".$tri."&first_air_date_year=".$annee);

        // convertit l'api de json en tableau
        $result = json_decode($json, true);

        // initialisation d'une variable tableau
        $tplArray = array();

        // boucle du nombre de résultat à partir de tableau $results à l'indice "results"
        for ($i = 0; $i < count($result['results']); $i++){
            // itération des différents indices qu'on va récupérer
            $tplArray[] = array(
                'id' => $result["results"][$i]["id"],
                'name' => $result["results"][$i]["original_name"],
                'datediff' => $result["results"][$i]["first_air_date"],
                'description' => $result["results"][$i]["overview"],
                'img' => $baseURI.$result["results"][$i]["poster_path"]
            );
        }
        
        //------------------- AJOUT DE SERIE DANS LA SECTION MES SERIES PAR L'UTILISATEUR ------------------//
    
        // Si notre formulaire est en POST
        if($request->isMethod('POST')) {
        
            // On va chercher l'utilisateur connecté
            $user = $this->getUser();
        
            // On appel l'entity manager
            $em = $this->getDoctrine()->getManager();
        
            // On instancie un nouvel objet Série
            $serie = new Serie();
            
            // On stock dans une variable la requête qui va rechercher le nom qui contient la valeur 'fav'
            $ajout = $request->request->get('fav');
        
            // On définit dans l'objet série l'utilisateur connecté et l'idApi à l'id de la série que l'utilisateur veut ajouter
            $serie
                ->setUser($user)
                ->setIdApi($ajout);

            // Recherche dans la table série une ligne qui combine l'idApi avec l'id du user connecté
            $repository = $em->getRepository(Serie::class);
            $doublon = $repository->findBy(['idApi' => $ajout, 'user' => $user]);

            //-------- Condition pour éviter les ajouts de doublon de série
            if(count($doublon) < 1 ) {

                // On enregistre en bdd l'objet série
                $em->persist($serie);
                $em->flush();

                // Message qui confirme que la série a bien été ajoutée
                $this->addFlash('success', 'Série ajoutée');
            } else {

                $this->addFlash('error', 'Vous avez déjà ajouter cette série');
            }
        }

        // appel des indices de tplArray dans test.twig
        return $this->render('series/index.html.twig', array(
            'array' => $tplArray,
            'page' => $page,
            'sort' => $tri,
            'year' => $annee,
            'genre' => $genre
        ));

    }

    //-------------------- PAGE DETAIL D'UNE SERIE --------------------//

    /**
     * @Route("/series/{id}", requirements={"id": "\d+"})
     */
    public function ficheSerie(Request $request, $id)
    {
        //La clé API
        $api = "f9966f8cc78884142eed6c6d4710717a";

        // La taille de l'image
        $size = "w342";
        // concaténer avec l'url de l'image
        $baseURI = "http://image.tmdb.org/t/p/" . $size;

        //appel à l'api
        $json = file_get_contents("https://api.themoviedb.org/3/tv/".$id."?api_key=".$api."&language=fr-FR");

        // convertit l'api de json en tableau
        $result = json_decode($json, true);

        // initialisation d'une variable tableau
        $ficheArray = array();

        // itération des différents indices qu'on va récupérer
        $ficheArray[] = array(
            'id_api' => $result["id"],
            'name' => $result["original_name"],
            'description' => $result["overview"],
            'network' => $result["networks"][0]["name"],
            'country' => $result["networks"][0]["origin_country"],
            'seasons' => $result["number_of_seasons"],
            'img' => $baseURI . $result["poster_path"]
        );

        $nb_season = array();

        for ($j = 0; $j < count($result['seasons']); $j++) {
            $nb_season[] = array(
                'season' => $result["seasons"][$j]["season_number"]
            );
        }

        $nb_genre = array();

        for ($g = 0; $g < count($result["genres"]); $g++) {
            $nb_genre[] = array(
                'name' => $result["genres"][$g]["name"]
            );
        }

        /* Aucune idée, de ce que sa fout là -_-
        if ($request->query->has('page')) {
            $season = $request->query->get('page');
        } else {
            $season = 1;
        }*/

        $episode = file_get_contents("https://api.themoviedb.org/3/tv/".$id."/season/".$season."?api_key=".$api."&language=fr-FR");

        $resultat = json_decode($episode, true);

        $nb_episode = array();

        for ($n = 0; $n< count($resultat["episodes"]); $n++) {
            $nb_episode[] = array(
                'name_episode' => $resultat["episodes"][$n]["name"],
                'num_episode' => $resultat["episodes"][$n]["episode_number"]
            );
        }


        // appel des indices de tplArray dans test.twig
        return $this->render('series/serie.html.twig', array(
            'fiche' => $ficheArray,
            'nb_season' => $nb_season,
            'nb_genre' => $nb_genre,
            'episodes' => $nb_episode
        ));
    }

    //---------------- PAGE DES SERIES AJOUTEES PAR L'UTILISATEUR ------------//

    /**
     * @Route("/mesSeries")
     */
    public function afficherFav()
    {
        // on récupère l'utilisateur connecté
        $user = $this->getUser();
        
        // on va chercher dans la table Série les objets users afin de récupérer les Id API qui leur correspond
        $repository = $this->getDoctrine()->getRepository(Serie::class);
        $series = $repository->findBy(['user' => $user]);
        
        
        //La clé API
        $api = "f9966f8cc78884142eed6c6d4710717a";
    
        // La taille de l'image
        $size = "w342";
        // concaténer avec l'url de l'image
        $baseURI = "http://image.tmdb.org/t/p/". $size;
        
        // On initialise un tableau vide (json_data) et une variable i à 0
        $json_data = [];
        $i = 0;
        
        // Pour chaque série que le user a ajouter:
        foreach ($series as $test) {
            
            // on définie la variable var à l'id API de la série
            $var = $test->getIdApi();
    
            //appel à l'api
            $varApi = file_get_contents("https://api.themoviedb.org/3/tv/" . $var . "?api_key=" . $api . "&language=fr-FR");
            
            // on transforme l'appel api en tableau json
            $json_table = json_decode($varApi, true);
    
            // >On boucle nos différents éléments pour chaque série
            $json_data[$i]["id"] = $json_table['id'];
            $json_data[$i]["poster_path"] = $baseURI . $json_table['poster_path'];
            $json_data[$i]["original_name"] = $json_table['original_name'];
            $json_data[$i]["fav_id"] = $test->getId();
            $i++;
        }


        return $this->render('series/mesSeries.html.twig',
            [
                'json_data' => $json_data
            ]);
    }

    //------------ RETIRER UNE SERIE DE LA PAGE MES SERIES ---------//

    /**
     * @Route("/mesSeries/{id}")
     */
    public function retirerFav($id)
    {
        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(Serie::class);

        $serie = $repository->find($id);

        if(!is_null($serie)){
            $em->remove($serie);
            $em->flush();

            $this->addFlash('success', 'Série supprimée');

            return $this->redirectToRoute('app_series_afficherfav');

        }
    }


}
