<?php

declare(strict_types=1);

namespace ServerNodeBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ServerNodeBundle\Entity\Node;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * @extends ServiceEntityRepository<Node>
 */
#[Autoconfigure(public: true)]
#[AsRepository(entityClass: Node::class)]
class NodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Node::class);
    }

    /**
     * 保存Node实体。
     *
     * @param Node $entity 要保存的实体
     * @param bool $flush 是否立即刷新EntityManager
     */
    public function save(Node $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除Node实体。
     *
     * @param Node $entity 要删除的实体
     * @param bool $flush 是否立即刷新EntityManager
     */
    public function remove(Node $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
