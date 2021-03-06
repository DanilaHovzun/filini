<?php

namespace Admin\Controller;

use Core\Controller\CoreController;
use Core\Entity\ConversionType;
use Core\Entity\Image;
use Core\Entity\Product;
use Core\Entity\Product\{
    Sofa, Chair, Stool, Bed
};
use Zend\View\Model\{
    ViewModel, JsonModel
};
use DoctrineModule\Validator\NoObjectExists;

class ProductController extends CoreController
{
    protected $conversionOptions = null;

    /**
     * Show list of products
     *
     * @return ViewModel
     */
    public function indexAction(): ViewModel
    {
        $page = $this->params()->fromQuery('page', 1);
        $limit = $this->params()->fromQuery('limit', 20);

        $productsCounter = [
            Product::TYPE_SOFA => $this->getRepository(Product::class)->getCountByDiscr(Sofa::class),
            Product::TYPE_CHAIR => $this->getRepository(Product::class)->getCountByDiscr(Chair::class),
            Product::TYPE_STOOL => $this->getRepository(Product::class)->getCountByDiscr(Stool::class),
            Product::TYPE_BED => $this->getRepository(Product::class)->getCountByDiscr(Bed::class),
        ];

        $query = $this->getRepository(Product::class)->findProducts();

        $paginator = $this->getPaginatorByQuery($query, $page, $limit);
        return new ViewModel([
            'paginator' => $paginator,
            'productsCounter' => $productsCounter,
        ]);
    }

    /**
     * Edit product action
     *
     * @return ViewModel
     */
    public function editAction(): ViewModel
    {
        /** @var \Core\Entity\Product $product */
        $product = $this->getEntity(Product::class, $this->params()->fromRoute('id'));
        if (!$product) {
            $product = $this->initProduct();
        }

        $form = $this->createProductForm($product);

        $request = $this->getRequest();
        if ($request->isPost()) {
            // Do not allow to change product slug if another product with such slug already exits.
            if ($product->getSlug() != $request->getPost('slug')) {
                $form = $this->attachSlugExistsValidator($form);
            }
        	$data = $request->getPost();
            $form->setData($data);
            if ($form->isValid()) {
                /**
                 * Initialize product images
                 */
                $product->getImages()->clear();
                if(!empty($data['images'])){
                    foreach($data['images'] as $image_id){
                        $image = $this->getEntity(Image::class, $image_id);
                        if ($image) {
                            $product->addImage($image);
                        }
                    }
                }

                $this->getEm()->persist($product);
                $this->getEm()->flush();
                return $this->redirect()->toRoute('admin_product');
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    /**
     * Remove product action
     *
     * @return ViewModel
     */
    public function removeAction(): ViewModel
    {
        $product = $this->getEntity(Product::class, $this->params()->fromRoute('id'));
        if ($product) {
            $this->getEm()->remove($product);
            $this->getEm()->flush();
        }
        return $this->redirect()->toRoute('admin_product');
    }

    /**
     * Upload image
     *
     * @return JsonModel
     */
    public function uploadImageAction(): JsonModel
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $image = $request->getFiles()->get('files')[0];
            $preset = 'product';

            //upload file to the server and create File entity
            if (!isset($image)) {
                return new JsonModel([
                    'success' => false,
                    'message' => $this->translate("The file was not uploaded"),
                ]);
            }

            // Upload file
            $uploadResult = $this->uploader()->upload($image, $preset);
            if (!$uploadResult['success']) {
                return new JsonModel([
                    'success' => false,
                    'message' => $uploadResult['message'],
                ]);
            }

            /** @var Core\Entity\File */
            $file = $uploadResult['file'];

            /** @var Core\Entity\Image */
            $image = $this->uploader()->generateImageEntity($file, $preset); // Generate Image Entity
            $this->uploader()->generateImagePresets($image, $preset); // Generate presests
            
            $data['files'][] = [
                'imageId' => $image->getId(),
                'name' =>  $file->getName(),
                'url' =>  $file->getRelativeUrl(),
                'thumbnailUrl' => $file->getRelativeUrl('preview'),
                'size' => $file->getSize(),
                'deleteUrl' => $this->url()->fromRoute('admin_product_category', ['action' => 'delete-image', 'id' => $image->getId()]),
                'deleteType' => 'DELETE',
            ];
            return new JsonModel($data);
        }
    }

    /**
     * Delete image
     *
     * @return JsonModel
     */
    public function deleteImageAction(): JsonModel
    {
        $image = $this->getEntity(Image::class, $this->params()->fromRoute('id'));
        if ($image) {
            $this->getEm()->remove($image);
            $this->getEm()->flush();
        }
        return new JsonModel(['data' => 'deleted']);
    }

    /**
     * Toggle image type
     *
     * @return JsonModel
     */
    public function toggleImageTypeAction(): JsonModel
    {
        /** @var \Core\Entity\Image $image */
        $image = $this->getEntity(Image::class, $this->params()->fromRoute('id'));
        if ($image) {
            $newType = $image->getImageType() == 'product' ? 'product_schema' : 'product';
            $image->setImageType($newType);
            $this->getEm()->persist($image);
            $this->getEm()->flush();
        }

        return new JsonModel([
            'type' => $newType,
            'label' => $newType == 'product' ? 'сделать чертежом' : 'сделать иллюстрацией',
            'typeLabel' => $newType == 'product' ? 'иллюстрация' : 'чертёж',
        ]);
    }

    /**
     * Init product
     *
     * @param string $defaultType
     * @return Core\Entity\Product
     */
    protected function initProduct($defaultType = Product::TYPE_SOFA)
    {
        $type = $this->params()->fromQuery('type', $defaultType);
        switch ($type) {
            case Product::TYPE_SOFA:
                $product = new Sofa();
                break;
            case Product::TYPE_CHAIR:
                $product = new Chair();
                break;
            case Product::TYPE_STOOL:
                $product = new Stool();
                break;
            case Product::TYPE_BED:
                $product = new Bed();
                break;
            default:
                $product = new Sofa();
                break;
        }

        return $product;
    }

    /**
     * @param \Core\Entity\Product $product
     * @param string $defaultType
     * @return \Zend\Form\Form
     */
    protected function createProductForm($product, $defaultType=Product::TYPE_SOFA)
    {
        $type = $this->params()->fromQuery('type', $defaultType);

        $form = $this->createForm($product);
        /** @var \Zend\Form\Element\Select $conversionElement */
        $conversionElement = $form->get('conversionType');
        $options = $conversionElement->getValueOptions();
        foreach ($this->getConversionOptions() as $conversionOption) {
            /** @var ConversionType $conversionOption */
            $options[$conversionOption->getId()] = $conversionOption->getName();
        }
        $conversionElement->setValueOptions($options);
        $form->setAttributes([
            'action' => $this->url()->fromRoute('admin_product', [
                'action' => 'edit',
                'id' => $product->getId()
            ], [
                'query' => [
                    'type' => $type
                ]
            ]),
        ]);
        return $form;
    }

    protected function getConversionOptions()
    {
        if ($this->conversionOptions === null) {
            $this->conversionOptions = $this->getRepository(ConversionType::class)->findAll();
        }

        return $this->conversionOptions;
    }

    /**
     * @var \Zend\Form\Form
     * @return \Zend\Form\Form
     */
    protected function attachSlugExistsValidator($form)
    {
        $form->getInputFilter()->get('slug')->getValidatorChain()->attach(new NoObjectExists([
            'object_repository' => $this->getRepository(Product::class),
            'fields' => ['slug'],
            'messages' => [
                NoObjectExists::ERROR_OBJECT_FOUND => "Slug already exists in database.",
            ]
        ]));

        return $form;
    }
}
