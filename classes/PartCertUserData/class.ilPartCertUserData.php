<?php
class ilPartCertUserData {
	protected int $part_cert_usr_id;

	protected ?string $part_cert_firstname;

	protected ?string $part_cert_lastname;

	protected string $part_cert_username;

    protected ?string $part_cert_gender;

    protected ?string $part_cert_salutation;

    /**
     * @return int
     */
    public function getPartCertUsrId(): int
    {
		return $this->part_cert_usr_id;
	}

    /**
     * @param int $part_cert_usr_id
     * @return void
     */
	public function setPartCertUsrId(int $part_cert_usr_id): void
    {
		$this->part_cert_usr_id = $part_cert_usr_id;
	}

    /**
     * @return string|null
     */
	public function getPartCertFirstname(): ?string
    {
		return $this->part_cert_firstname;
	}

    /**
     * @param string|null $part_cert_firstname
     * @return void
     */
	public function setPartCertFirstname(?string $part_cert_firstname): void
    {
		$this->part_cert_firstname = $part_cert_firstname;
	}

    /**
     * @return string|null
     */
	public function getPartCertLastname(): ?string
    {
		return $this->part_cert_lastname;
	}

    /**
     * @return string|null
     */
    public function getPartCertGender(): ?string
    {
        return $this->part_cert_gender;
    }

    /**
     * @param string|null $part_cert_gender
     * @return void
     */
    public function setPartCertGender(?string $part_cert_gender): void
    {
        $this->part_cert_gender = $part_cert_gender;
    }

    /**
     * @param string|null $part_cert_lastname
     * @return void
     */
	public function setPartCertLastname(?string $part_cert_lastname): void
    {
		$this->part_cert_lastname = $part_cert_lastname;
	}

    /**
     * @return string
     */
	public function getPartCertUserName(): string
    {
		return $this->part_cert_username;
	}

    /**
     * @param string $part_cert_username
     * @return void
     */
	public function setPartCertUserName(string $part_cert_username): void
    {
		$this->part_cert_username = $part_cert_username;
	}

    /**
     * @return string|null
     */
    public function getPartCertSalutation(): ?string
    {
        return $this->part_cert_salutation;
    }

    /**
     * @param string|null $part_cert_salutation
     * @return void
     */
    public function setPartCertSalutation(?string $part_cert_salutation): void
    {
        $this->part_cert_salutation = $part_cert_salutation;
    }

    /**
     * @param string $salutation
     * @param string $firstname
     * @param string $lastname
     * @return bool
     */
    public function checkIfUserDataFilled(
        string $salutation,
        string $firstname,
        string $lastname
    ): bool {
        if (empty($salutation) &&
            empty($firstname) &&
            empty($lastname)
        ) {
            return false;
        }
        return true;
    }
}
