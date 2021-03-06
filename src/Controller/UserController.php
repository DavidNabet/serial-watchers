<?php
/**
 * Created by PhpStorm.
 * User: julialuquot
 * Date: 2018-12-07
 * Time: 10:11
 */

namespace App\Controller;


use App\Entity\Episode;
use App\Entity\Serie;
use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\DateTime;

/**
 * @Route("/user")
 * Class UserController
 * @package App\Controller
 */
class UserController extends AbstractController
{
    /**
     * @Route("/dashboard")
     */
    public function dashboard()
    {
        $repository = $this->getDoctrine()->getRepository(User::class);
    
        $user = $repository->findBy([], ['firstname' => 'asc']);
    
        //afficher date
        $formatter = new \IntlDateFormatter('fr_FR', \IntlDateFormatter::LONG, \IntlDateFormatter::LONG);
        $formatter->setPattern('MMMM');
        $now = new \DateTime();
        $month = $formatter->format($now);
    
    
        // La taille de l'image
        $size = "w342";
        // concaténer avec l'url de l'image
        $baseURI = "http://image.tmdb.org/t/p/" . $size;
        
        //La clé API
        $api = "f9966f8cc78884142eed6c6d4710717a";
    
        //appel à l'api
    
        $json = file_get_contents("https://api.themoviedb.org/3/tv/airing_today?api_key=" . $api . "&language=fr-FR");
    
    
        // convertit l'api de json en tableau
        $results = json_decode($json, true);
    dump($results);
        // initialisation d'une variable tableau
        $ficheArray = array();
    
        // itération des différents indices qu'on va récupérer
    
        // itération des différents indices qu'on va récupérer
    
        foreach ($results['results'] as $result) {
//            $lastAirDate = new \DateTime($result["last_air_date"]);
        
            $ficheArray[] = array(
                'id' => $result["id"],
                'name' => $result["original_name"],
                'language' => $result["original_language"],
                'img' => $baseURI . $result["poster_path"]
//                'date' => $lastAirDate,
//                'month' => $formatter->format($lastAirDate),
//                "episode_run_time" => $result["episode_run_time"]
            );
        
        }
        // appel des indices de tplArray dans test.twig
        return $this->render('user/dashboard.html.twig', array(
            'fiche' => $ficheArray,
            'user' => $user,
            'date' => $now,
            'month' => $month
        ));


    }



//    FONCTION MISE A JOUR PROFIL

    /**
     * @Route("/update-user")
     */

    public function updateUser(Request $request)
    {
        $id = $this->getUser();

        $em = $this->getDoctrine()->getManager();
        $repository = $em->getRepository(User::class);
        // objet User dont l'id en bdd est celui reçu dans l'url
        $user = $repository->find($id);


        if ($request->isMethod('POST')) {
            $user
                ->setPseudo($request->request->get('pseudo'))
                ->setEmail($request->request->get('email'))
                ->setFirstname($request->request->get('firstname'))
                ->setLastname($request->request->get('lastname'));

            //$emailDuForm = $request->request->get('email');
            //$user->setEmail($emailDuForm);

            $em->persist($user);
            $em->flush();
        }

        return $this->render(
            'user/profil/update-user.html.twig', [
                'user' => $user
            ]
        );
    }


//    FONCTION SERIES POPULAIRES


    /**
     * @Route("/newseries")
     */
    public function newSerie()
    {

        //pour appeler les series populaires:

        $api = "f9966f8cc78884142eed6c6d4710717a";
        $size = "w342";
        $baseURI = 'http://image.tmdb.org/t/p/' . $size;


        $json = file_get_contents("https://api.themoviedb.org/3/tv/popular?api_key=" . $api . "&language=fr-FR&page=1");

        $result2 = json_decode($json, true);

        $SerieNew = array();

        for ($i = 0; $i < count($result2['results']); $i++) {
            $SerieNew[] = array(
                'id' => $result2['results'][$i]["id"],
                'img' => $baseURI . $result2['results'][$i]['poster_path'],
                'name' => $result2['results'][$i]["original_name"],
                'datediff' => $result2['results'][$i]["first_air_date"],
                'description' => $result2['results'][$i]["overview"],
                'country' => $result2['results'][$i]["origin_country"]
            );
        }


        return $this->render(
            'user/series/newSeries.html.twig',
            [
                'new' => $SerieNew
            ]);

    }

    //    FONCTION PROCHAINES SERIES


    /**
     * @Route("/nextseries")
     */
    public function nextSeries()
    {

        //pour appeler les nouvelles series:

        $api = "f9966f8cc78884142eed6c6d4710717a";

        $size = "w342";
        $baseURI = 'http://image.tmdb.org/t/p/' . $size;

        $json = file_get_contents("https://api.themoviedb.org/3/tv/on_the_air?api_key=" . $api . "&language=fr-FR&page=1");

        $result3 = json_decode($json, true);

        $SerieNext = array();

        for ($i = 0; $i < count($result3['results']); $i++) {
            $SerieNext[] = array(
                'id' => $result3['results'][$i]["id"],
                'img' => $baseURI . $result3['results'][$i]['poster_path'],
                'name' => $result3['results'][$i]["original_name"],
                'datediff' => $result3['results'][$i]["first_air_date"],
                'description' => $result3['results'][$i]["overview"],
                'country' => $result3['results'][$i]["origin_country"]
            );
        }


        return $this->render(
            'user/series/nextSeries.html.twig',
            [
                'next' => $SerieNext
            ]);
    }

}







