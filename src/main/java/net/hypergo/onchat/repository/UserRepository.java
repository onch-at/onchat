package net.hypergo.onchat.repository;

import net.hypergo.onchat.domain.User;
import org.springframework.data.repository.PagingAndSortingRepository;

public interface UserRepository extends PagingAndSortingRepository<User, Long> {
}
