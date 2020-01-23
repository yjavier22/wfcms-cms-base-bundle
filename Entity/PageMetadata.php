<?php

namespace Wf\Bundle\CmsBaseBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JMS\Serializer\Annotation as JMSS;

/**
 * @ORM\MappedSuperclass
 * @ORM\HasLifecycleCallbacks
 */
abstract class PageMetadata
{
    /**
     * @ORM\Id
     * @ORM\Column(type="integer")
     * @ORM\GeneratedValue(strategy="AUTO")
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64, nullable=true, unique=true)
     * @JMSS\Groups({"list", "edit", "version"})
     * @JMSS\Type("string")
     */
    protected $checksum;

    /**
     * @ORM\Column(type="json_array", length=65535, name="allowed_modules", nullable=true)
     * @JMSS\Exclude
     */
    private $allowedModules = array();

    /**
     * @ORM\Column(type="json_array", length=65535, name="new_modules", nullable=true)
     * @JMSS\Exclude
     */
    private $newModules = array();

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        $this->id = $id;
    }

    public function getChecksum()
    {
        return $this->checksum;
    }

    public function setChecksum($checksum)
    {
        $this->checksum = $checksum;
    }

    public function getAllowedModules()
    {
        return $this->allowedModules;
    }

    public function getNewModules()
    {
        return $this->newModules;
    }

    public function setAllowedModules($allowedModules)
    {
        $this->allowedModules = $allowedModules;
    }

    public function setNewModules($newModules)
    {
        $this->newModules = $newModules;
    }

    public function getUpdateChecksum()
    {
        $this->setChecksum(
            sha1(
                json_encode(
                    array(
                        'allowedModules' => $this->allowedModules,
                        'newModules' => $this->newModules,
                    )
                )
            )
        );

        return $this->checksum;
    }
}
