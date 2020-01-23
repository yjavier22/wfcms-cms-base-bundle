<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Symfony\Component\Security\Core\Role\Role;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use FOS\UserBundle\Entity\User as BaseUser;
use JMS\Serializer\Annotation as JMSS;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * Wf\Bundle\CmsBaseBundle\Entity\User
 * @JMSS\ExclusionPolicy("ALL")
 * @ORM\MappedSuperclass
 */
abstract class User extends BaseUser
{
    /*
    const ROLE_EDITOR = 'ROLE_EDITOR';
    const ROLE_COMMERCIAL = 'ROLE_COMMERCIAL';
    const ROLE_PUBLISHER = 'ROLE_PUBLISHER';
    const ROLE_USER_MANAGER = 'ROLE_USER_MANAGER';
    */

    const ROLE_TRANSLATOR = 'ROLE_TRANSLATOR';

    const ROLE_USER_ADD = 'ROLE_USER_ADD';
    const ROLE_USER_EDIT = 'ROLE_USER_EDIT';
    const ROLE_USER_REMOVE = 'ROLE_USER_REMOVE';

    const ROLE_EDITION_CREATE = 'ROLE_EDITION_CREATE';
    const ROLE_EDITION_REMOVE = 'ROLE_EDITION_REMOVE';
    const ROLE_EDITION_EDIT_META = 'ROLE_EDITION_EDIT_META';
    const ROLE_EDITION_PUBLISH = 'ROLE_EDITION_PUBLISH';

    const ROLE_CONTENT_PAGE_ADD = 'ROLE_CONTENT_PAGE_ADD';
    const ROLE_CONTENT_PAGE_EDIT = 'ROLE_CONTENT_PAGE_EDIT';
    const ROLE_CONTENT_PAGE_REMOVE = 'ROLE_CONTENT_PAGE_REMOVE';
    const ROLE_CONTENT_PAGE_PUBLISH = 'ROLE_CONTENT_PAGE_PUBLISH';
    const ROLE_CONTENT_PAGE_SEND_TO_VALIDATION = 'ROLE_CONTENT_PAGE_SEND_TO_VALIDATION';
    const ROLE_CONTENT_PAGE_VALIDATE = 'ROLE_CONTENT_PAGE_VALIDATE';

    const ROLE_AD_PAGE_ADD = 'ROLE_AD_PAGE_ADD';
    const ROLE_AD_PAGE_EDIT = 'ROLE_AD_PAGE_EDIT';
    const ROLE_AD_PAGE_REMOVE = 'ROLE_AD_PAGE_REMOVE';
    const ROLE_AD_PAGE_SEND_TO_VALIDATION = 'ROLE_AD_PAGE_SEND_TO_VALIDATION';
    const ROLE_AD_PAGE_VALIDATE = 'ROLE_AD_PAGE_VALIDATE';
    const ROLE_AD_PAGE_PUBLISH = 'ROLE_AD_PAGE_PUBLISH';

    const ROLE_BLOG_ADMIN = 'ROLE_BLOG_ADMIN';

    const ROLE_SEARCH = 'ROLE_SEARCH';

    /**
     * @var integer $id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @JMSS\Expose
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @ORM\ManyToMany(targetEntity="Group", cascade={"persist"})
     * @ORM\JoinTable(name="wf_user_group",
     *      joinColumns={@ORM\JoinColumn(name="user_id", referencedColumnName="id")},
     *      inverseJoinColumns={@ORM\JoinColumn(name="group_id", referencedColumnName="id")}
     *      )
     */
    protected $groups;

    /**
     * @ORM\ManyToOne(targetEntity="Image")
     * @ORM\JoinColumn(name="avatar_id", referencedColumnName="id", nullable=true)
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("Wf\Bundle\CmsBaseBundle\Entity\Image")
     * @JMSS\Expose
     * @var Wf\Bundle\CmsBaseBundle\Entity\Image
     */
    protected $avatar;

    public function __construct()
    {
        parent::__construct();
        $this->groups = new ArrayCollection();
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    public function setGroups($groups)
    {
        $this->groups = $groups;
    }

    public function getGroups()
    {
        return $this->groups;
    }

    public function getAvatar()
    {
        return $this->avatar;
    }

    public function setAvatar($avatar)
    {
        $this->avatar = $avatar;
    }
    /**
     * @ORM\Column(name="first_name", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="form.error.user.first_name.notblank")
     * @JMSS\Expose
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("string")
     * @var string
     */
    protected $firstName;


    /**
     * @ORM\Column(name="full_name", type="string", length=255, nullable=true)
     * @JMSS\Expose
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("string")
     * @var string
     */
    protected $fullName;

    /**
     * @ORM\Column(name="last_name", type="string", length=255, nullable=true)
     * @Assert\NotBlank(message="form.error.user.last_name.notblank")
     * @JMSS\Expose
     * @JMSS\Groups({"edit", "list", "version"})
     * @JMSS\Type("string")
     * @var string
     */
    protected $lastName;

    /**
     * @Gedmo\Slug(fields={"firstName", "lastName"}, updatable=true)
     * @ORM\Column(name="slug", type="string", length=128, unique=true)
     * @JMSS\Type("string")
     * @JMSS\Groups({"edit", "list"})
     * @var string
     */
    protected $slug;

    /**
     * @ORM\Column(name="twitter", type="string", length=64, nullable=true)
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     * @var string
     */
    protected $twitter;

    /**
     * @ORM\Column(name="facebook", type="string", length=64, nullable=true)
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     * @var string
     */
    protected $facebook;

    /**
     * @ORM\Column(name="job", type="string", length=128, nullable=true)
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     * @var string
     */
    protected $job;

    /**
     * @ORM\Column(name="description", type="string", length=256, nullable=true)
     * @JMSS\Groups({"edit", "list"})
     * @JMSS\Type("string")
     * @JMSS\Expose
     * @var string
     */
    protected $description;

    /**
     * @ORM\Column(name="columnist", type="boolean")
     * @JMSS\Exclude
     * @var boolean
     */
    protected $columnist = false;

    /**
     * @JMSS\Groups({"list", "edit"})
     * @JMSS\Type("string")
     * @JMSS\Accessor(getter="getName")
     * @JMSS\Expose
     */
    protected $name;

    /**
     * @ORM\Column(name="contact", type="string", length=255, nullable=true)
     * @var string
     */
    protected $contact;

    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;
    }

    public function getFirstName()
    {
        return $this->firstName;
    }

    public function setLastName($lastName)
    {
        $this->lastName = $lastName;
    }

    public function getLastName()
    {
        return $this->lastName;
    }

    public function getName()
    {
        $firstName = $this->getFirstName();
        $lastName = $this->getLastName();

        if (empty($firstName) && empty($lastName)) {
            return $this->getUsername();
        }

        return $firstName . ' ' . $lastName;
    }

    public function getRolesObjects()
    {
        $roles = $this->getRoles();

        $ret = array();

        foreach ($roles as $role) {
            $ret[$role] = new Role($role);
        }

        return $ret;
    }

    public function getExpiresAt()
    {
        return $this->expiresAt;
    }

    public function getCredentialsExpireAt()
    {
        return $this->credentialsExpireAt;
    }

    public function getTwitter() {
        return $this->twitter;
    }

    public function setTwitter($twitter) {
        $this->twitter = $twitter;
    }

    public function getFacebook() {
        return $this->facebook;
    }

    public function setFacebook($facebook) {
        $this->facebook = $facebook;
    }

    public function getJob() {
        return $this->job;
    }

    public function setJob($job) {
        $this->job = $job;
    }

    public function getDescription() {
        return $this->description;
    }

    public function setDescription($description) {
        $this->description = $description;
    }

    public function getSlug() {
        return $this->slug;
    }

    public function setSlug($slug) {
        $this->slug = $slug;
    }

    /**
     * get the columnist flag
     * @return boolean
     */
    public function isColumnist()
    {
        return $this->columnist;
    }

    /**
     * set the columnist flag
     * @param boolean $columnist
     */
    public function setColumnist($columnist)
    {
        $this->columnist = $columnist;
    }

    public function getContact()
    {
        return $this->contact;
    }

    public function setContact($contact)
    {
        $this->contact = $contact;
    }

    public function setFullName($fullName)
    {
        $this->fullName = $fullName;
    }

    public function getFullName()
    {
        return $this->fullName;
    }
}