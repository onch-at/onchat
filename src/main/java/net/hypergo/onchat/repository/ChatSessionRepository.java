package net.hypergo.onchat.repository;

import net.hypergo.onchat.domain.ChatSession;
import org.springframework.data.repository.CrudRepository;

public interface ChatSessionRepository extends CrudRepository<ChatSession, Long> {
}
