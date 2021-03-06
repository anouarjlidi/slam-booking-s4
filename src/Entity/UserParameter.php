<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="user_parameter", uniqueConstraints={@ORM\UniqueConstraint(name="uk_user_parameter",columns={"user_id", "parameter_group", "parameter"})})
 * @ORM\Entity(repositoryClass="App\Repository\UserParameterRepository")
 * @ORM\HasLifecycleCallbacks()
 */
class UserParameter
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(name="parameter_group", type="string", length=255)
     */
    private $parameterGroup;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $parameter;

    /**
     * @ORM\Column(name="parameter_type", type="string", length=255)
     */
    private $parameterType;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $integerValue;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $stringValue;

    /**
     * @ORM\Column(type="boolean")
     */
    private $booleanValue;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\User", inversedBy="userParameters")
     * @ORM\JoinColumn(nullable=false)
     */
    private $user;

    public function getId()
    {
        return $this->id;
    }

    public function getParameterGroup(): ?string
    {
        return $this->parameterGroup;
    }

    public function setParameterGroup(string $parameterGroup): self
    {
        $this->parameterGroup = $parameterGroup;

        return $this;
    }

    public function getParameter(): ?string
    {
        return $this->parameter;
    }

    public function setParameter(string $parameter): self
    {
        $this->parameter = $parameter;

        return $this;
    }

    public function getParameterType(): ?string
    {
        return $this->parameterType;
    }

    public function setParameterType(string $parameterType): self
    {
        $this->parameterType = $parameterType;

        return $this;
    }

    public function getIntegerValue(): ?int
    {
        return $this->integerValue;
    }

    public function setIntegerValue(?int $integerValue): self
    {
        $this->integerValue = $integerValue;

        return $this;
    }

    public function getStringValue(): ?string
    {
        return $this->stringValue;
    }

    public function setStringValue(?string $stringValue): self
    {
        $this->stringValue = $stringValue;

        return $this;
    }

    public function getBooleanValue(): ?bool
    {
        return $this->booleanValue;
    }

    public function setBooleanValue(bool $booleanValue): self
    {
        $this->booleanValue = $booleanValue;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

	public function __construct(\App\Entity\User $user, $parameterGroup, $parameter) {
		$this->setUser($user);
		$this->setParameterGroup($parameterGroup);
		$this->setParameter($parameter);
		$this->setBooleanValue(false);
    }

    public function setSBIntegerValue(?int $integerValue): self
	{
		$this->setIntegerValue($integerValue);
        $this->setParameterType('integer');
		return $this;
    }

    public function setSBStringValue(?string $stringValue): self
    {
		$this->setStringValue($stringValue);
		$this->setParameterType('string');
		return $this;
    }

    public function setSBBooleanValue(bool $booleanValue): self
    {
		$this->setBooleanValue($booleanValue);
		$this->setParameterType('boolean');
		return $this;
	}

    /**
    * @ORM\PrePersist
    */
    public function createDate()
    {
        $this->createdAt = new \DateTime();
    }

    /**
    * @ORM\PreUpdate
    */
    public function updateDate()
    {
        $this->updatedAt = new \DateTime();
    }
}
