package net.hypergo.onchat.repository;

import net.hypergo.onchat.domain.UserInfo;
import org.springframework.data.repository.PagingAndSortingRepository;

public interface UserInfoRepository extends PagingAndSortingRepository<UserInfo, Long> {
}
