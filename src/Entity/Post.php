<?php

namespace App\Entity;

use App\Repository\PostRepository;
use App\State\PostAuthorProcessor;
use ApiPlatform\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Metadata\ApiFilter;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Post as ApiPost;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ApiFilter(SearchFilter::class, properties: ['content' => 'partial', 'author.id' => 'exact', 'author.username' => 'partial'])]
#[ApiResource(
    operations: [
        new Get(
            normalizationContext: ['groups' => ['post:read']]
        ),
        new GetCollection(
            normalizationContext: ['groups' => ['post:read']]
        ),
        new ApiPost(
            security: "is_granted('ROLE_USER')",
            denormalizationContext: ['groups' => ['post:write']],
            normalizationContext: ['groups' => ['post:read']],
            processor: PostAuthorProcessor::class
        ),
        new Delete(
            security: "is_granted('ROLE_USER') and object.getAuthor() == user"
        ),
    ],
    order: ['createdAt' => 'DESC']
)]
class Post
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['post:read'])]
    private ?int $id = null;

    #[ORM\Column(type: 'text')]
    #[Groups(['post:read', 'post:write'])]
    #[Assert\NotBlank(message: 'Le contenu est obligatoire.')]
    #[Assert\Length(
        min: 3,
        minMessage: 'Le post doit contenir au moins {{ limit }} caractères.'
    )]
    private ?string $content = null;

    #[ORM\Column]
    #[Groups(['post:read'])]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\ManyToOne(inversedBy: 'posts')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['post:read'])]
    private ?User $author = null;

    /**
     * @var Collection<int, User>
     */
    #[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'likedPosts')]
    #[ORM\JoinTable(name: 'post_likes')]
    private Collection $likedBy;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->likedBy = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): static
    {
        $this->content = $content;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getAuthor(): ?User
    {
        return $this->author;
    }

    public function setAuthor(?User $author): static
    {
        $this->author = $author;

        return $this;
    }

    /**
     * @return Collection<int, User>
     */
    public function getLikedBy(): Collection
    {
        return $this->likedBy;
    }

    public function likeBy(User $user): static
    {
        if (!$this->likedBy->contains($user)) {
            $this->likedBy->add($user);
        }

        return $this;
    }

    public function unlikeBy(User $user): static
    {
        $this->likedBy->removeElement($user);

        return $this;
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likedBy->contains($user);
    }

    public function getLikesCount(): int
    {
        return $this->likedBy->count();
    }
}
