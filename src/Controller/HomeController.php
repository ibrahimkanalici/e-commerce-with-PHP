<?php

namespace App\Controller;

use App\Entity\Admin\Messages;
use App\Entity\Admin\Product;
use App\Entity\User;
use App\Form\Admin\MessagesType;
use App\Form\UserType;
use App\Repository\Admin\CategoryRepository;
use App\Repository\Admin\ProductRepository;
use App\Repository\Admin\ImageRepository;
use App\Repository\Admin\SettingRepository;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    /**
     * @Route("/home", name="home")
     */
    public function index(SettingRepository $settingRepository, CategoryRepository $categoryRepository)
    {
        $data = $settingRepository->findAll();


        //Get data for slider *******************
        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM product WHERE status= 'True' ORDER BY ID DESC LIMIT 5";

        $statement = $em->getConnection()->prepare($sql);
        // $statement->bindValue('parentid',$parent);
        $statement->execute();
        $sliders = $statement->fetchAll();

        //content
        $ek=$this->getDoctrine()->getManager();
        $sql = "SELECT * FROM product WHERE status= 'True' ORDER BY ID ASC LIMIT 15";
        $statement = $ek->getConnection()->prepare($sql);
        // $statement->bindValue('pid',$parent);
        $statement->execute();
        $producte = $statement->fetchAll();


        //dump($sliders);
        //die();
        $cats = $this->categoryTree();
        $cats[0] = '<ul id = "menu-v"';
        //dump($cats);
        // die();
        return $this->render('home/index.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'sliders' => $sliders,
            'producte'=>$producte,
        ]);

    }

    // Recursive php function for category tree
    public function categoryTree($parent = 0, $user_tree_array = '')
    {

        if (!is_array($user_tree_array))
            $user_tree_array = array();

        $em = $this->getDoctrine()->getManager();
        $sql = "SELECT * FROM category WHERE status='True' AND parentid =" . $parent;

        $statement = $em->getConnection()->prepare($sql);
        $statement->execute();
        $result = $statement->fetchAll();

        if (count($result) > 0) {
            $user_tree_array[] = "<ul>";
            foreach ($result as $row) {
                $user_tree_array[] = "<li><a href='/category/" . $row['id'] . "'>" . $row['title'] . "</a>";
                $user_tree_array = $this->categoryTree($row['id'], $user_tree_array);
            }
            $user_tree_array[] = "</li></ul>";

        }
        return $user_tree_array;
    }

    /**
     * @Route("/category/{catid}", name="category_products", methods="GET")
     */
    public function CategoryProducts($catid, CategoryRepository $categoryRepository)
    {
        $cats = $this->categorytree();
        $cats[0] = '<ul id="menu-v">';
        $data = $categoryRepository->findBy([
            'id' => $catid
        ]);
        //dump($data);
        $em = $this->getDoctrine()->getManager();
        $sql = 'SELECT * FROM product WHERE status="True" AND category_id= :catid';
        $statement = $em->getConnection()->prepare($sql);
        $statement->bindValue('catid', $catid);
        $statement->execute();
        $products = $statement->fetchAll();
       // dump($result);
        //die();
        return $this->render('home/products.html.twig', [
            'data' => $data,
            'products' => $products,
            'cats' => $cats,

        ]);



    }

    /**
     * @Route("/product/{id}", name="product_detail", methods="GET")
     */
    public function ProductDetail($id, ProductRepository $productRepository, Product $product, ImageRepository $imageRepository)
    {

        $data = $productRepository->findBy(
            ['id' => $id]
        );

        $images = $imageRepository->findBy(
            ['product_id' => $id]
        );
        $cats = $this->categoryTree();
        $cats[0] = '<ul id="menu-v">';

        return $this->render('home/product_detail.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'products' => $product,
            'images' => $images,


        ]);

    }


    /**
     * @Route("/hakkimizda", name="hakkimizda")
     */
    public function hakkimizda(SettingRepository $settingRepository, CategoryRepository $categoryRepository)
    {
        $cats = $this->categoryTree();
        $data = $settingRepository->findAll();
        return $this->render('home/hakkimizda.html.twig', [
            'data' => $data,
            'cats' => $cats,
        ]);
    }

    /**
     * @Route("/referanslar", name="referanslar")
     */
    public function referanslar(SettingRepository $settingRepository, CategoryRepository $categoryRepository)
    {
        $cats = $this->categoryTree();
        $data = $settingRepository->findAll();
        return $this->render('home/referans.html.twig', [
            'data' => $data,
            'cats' => $cats,
        ]);
    }


    /**
     * @Route("/iletisim", name="iletisim",methods="GET|POST")
     */
    public function iletisim(SettingRepository $settingRepository, CategoryRepository $categoryRepository, Request $request)
    {
        $message = new Messages();
        $form = $this->createForm(MessagesType::class, $message);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');
        if ($form->isSubmitted()) {
            if ($this->isCsrfTokenValid('form-message', $submittedToken)) {
                $em = $this->getDoctrine()->getManager();
                $em->persist($message);
                $em->flush();
                $this->addFlash('success', 'Mesajınız Başarıyla Gönderilmiştir');

                return $this->redirectToRoute('iletisim');
            }
        }
        $cats = $this->categoryTree();
        $data = $settingRepository->findAll();
        //dump($data);
        //die();
        return $this->render('home/iletisim.html.twig', [
            'data' => $data,
            'cats' => $cats,
            'message' => $message,

        ]);

    }


    /**
     * @Route("/newuser", name="new_user",methods="GET|POST")
     */
    public function newuser(Request $request,CategoryRepository $categoryRepository,SettingRepository $settingRepository,UserRepository $userRepository): Response
    {
       //dump($request);
        //die();

        $data = $settingRepository->findAll();
        $user = new User();
        $form = $this->createForm(UserType::class, $user);
        $form->handleRequest($request);

        $submittedToken = $request->request->get('token');
        if ($this->isCsrfTokenValid('user-form',$submittedToken)) {
            if ($form->isSubmitted()) {
                $request->request->get($user->getEmail());
                $emaildata=$userRepository->findBy([
                    'email' =>$user->getEmail()
                ]);

                if($emaildata==null){
                    $em = $this->getDoctrine()->getManager();
                    $user->setRoles("ROLE_USER");
                    $em->persist($user);
                    $em->flush();
                    $this->addFlash('success','Üye kaydınız başarıyla gerçekleşmiştir.');

                    return $this->redirectToRoute('app_login');
                }

                else
                {
                    $this->addFlash('error', $user->getEmail()." " . 'Bu e-mail adresi kayıtlıdır!');

                }
            }
        }

        $cats =$this->categoryTree();
        $cats[0] = '<ul id = "menu-v"';
        return $this->render('home/newuser.html.twig', [
            'user' => $user,
            'data' => $data,
            'cats' =>$cats,
            'form' => $form->createView(),

        ]);

    }
}
