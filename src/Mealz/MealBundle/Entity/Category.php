<?php

namespace App\Mealz\MealBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table()
 * @ORM\Entity(repositoryClass="App\Mealz\MealBundle\Entity\CategoryRepository")
 *
 * @SuppressWarnings(PHPMD.CamelCasePropertyName)
 * @SuppressWarnings(PHPMD.CamelCaseVariableName)
 * @SuppressWarnings(PHPMD.CamelCaseParameterName)
 */
class Category
{
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @Gedmo\Slug(fields={"title_en"})
     * @ORM\Column(length=128, unique=true)
     *
     * @var string
     */
    protected $slug;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=FALSE)
     *
     * @var string
     */
    protected $title_en;

    /**
     * @Assert\NotBlank()
     * @Assert\Length(max=255)
     * @ORM\Column(type="string", length=255, nullable=FALSE)
     *
     * @var string
     */
    protected $title_de;

    /**
     * @var string
     */
    protected $currentLocale = 'en';

    /**
     * @ORM\OneToMany(targetEntity="Dish", mappedBy="category")
     *
     * @var ArrayCollection
     */
    protected $dishes;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getSlug()
    {
        return $this->slug;
    }

    /**
     * @param int $id
     *
     * @SuppressWarnings (PHPMD.ShortVariable)
     */
    public function setId($id): void
    {
        $this->id = $id;
    }

    /**
     * @return string
     */
    public function getTitleEn()
    {
        return $this->title_en;
    }

    /**
     * @param string $title_en
     */
    public function setTitleEn($title_en): void
    {
        $this->title_en = $title_en;
    }

    /**
     * @return string
     */
    public function getTitleDe()
    {
        return $this->title_de;
    }

    /**
     * @param string $title_de
     */
    public function setTitleDe($title_de): void
    {
        $this->title_de = $title_de;
    }

    /**
     * @return string
     */
    public function getTitle()
    {
        if ('de' == $this->currentLocale && $this->title_de) {
            return $this->getTitleDe();
        }

        return $this->getTitleEn();
    }

    public function __toString()
    {
        return $this->getTitle();
    }

    /**
     * @param string $currentLocale
     */
    public function setCurrentLocale($currentLocale): void
    {
        $this->currentLocale = $currentLocale;
    }

    /**
     * @return string
     */
    public function getCurrentLocale()
    {
        return $this->currentLocale;
    }

    /**
     * @return ArrayCollection
     */
    public function getDishes()
    {
        return $this->dishes;
    }
}
