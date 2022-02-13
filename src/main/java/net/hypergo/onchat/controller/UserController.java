package net.hypergo.onchat.controller;

import net.hypergo.onchat.domain.User;
import net.hypergo.onchat.repository.UserRepository;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
public class UserController {
    private final UserRepository repository;

    public UserController(UserRepository repository) {
        this.repository = repository;
    }

    @GetMapping("/get")
    public User get() {
        return repository.findById(1L).get();
    }
}
